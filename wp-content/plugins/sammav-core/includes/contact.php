<?php
/**
 * Contact form processing (FR-C1 – C5): validation, spam checks, emails and storage.
 *
 * The form posts to admin-post.php so it works without JavaScript; view.js
 * submits the same fields to the REST route and shows errors inline.
 * No nonce is used on purpose: the form is public and must keep working behind
 * page caching. Spam is handled by a honeypot, a signed time-trap, per-IP rate
 * limiting and optional Cloudflare Turnstile.
 */

defined( 'ABSPATH' ) || exit;

const SAMMAV_MIN_SECONDS = 3;
const SAMMAV_RATE_LIMIT  = 5; // submissions per IP per 10 minutes.

function sammav_budget_options(): array {
	return array(
		''           => __( 'Prefer not to say', 'sammav-core' ),
		'under-5k'   => __( 'Under GHS 5,000', 'sammav-core' ),
		'5k-20k'     => __( 'GHS 5,000 – 20,000', 'sammav-core' ),
		'20k-50k'    => __( 'GHS 20,000 – 50,000', 'sammav-core' ),
		'over-50k'   => __( 'Over GHS 50,000', 'sammav-core' ),
	);
}

function sammav_form_token(): string {
	$time = (string) time();
	return $time . '.' . hash_hmac( 'sha256', $time, wp_salt( 'nonce' ) );
}

function sammav_client_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Validates and processes a submission.
 *
 * @return array{ok: bool, errors: array<string,string>, message: string}
 */
function sammav_process_enquiry( array $input ): array {
	$fail = static fn( array $errors, string $message = '' ) => array(
		'ok'      => false,
		'errors'  => $errors,
		'message' => $message ?: __( 'Please correct the highlighted fields and try again.', 'sammav-core' ),
	);

	$data = array(
		'name'    => sanitize_text_field( $input['sv_name'] ?? '' ),
		'email'   => sanitize_email( $input['sv_email'] ?? '' ),
		'phone'   => sanitize_text_field( $input['sv_phone'] ?? '' ),
		'company' => sanitize_text_field( $input['sv_company'] ?? '' ),
		'service' => sanitize_text_field( $input['sv_service'] ?? '' ),
		'budget'  => sanitize_key( $input['sv_budget'] ?? '' ),
		'message' => sanitize_textarea_field( $input['sv_message'] ?? '' ),
		'consent' => ! empty( $input['sv_consent'] ),
	);

	// Spam checks. Bots get a generic failure with no field detail.
	$spam_message = __( 'Your message could not be sent. Please try again or contact us by phone or email.', 'sammav-core' );
	if ( ! empty( $input['sv_website'] ) ) {
		return $fail( array(), $spam_message );
	}
	$token = explode( '.', (string) ( $input['sv_token'] ?? '' ) );
	if ( 2 !== count( $token ) || ! hash_equals( hash_hmac( 'sha256', $token[0], wp_salt( 'nonce' ) ), $token[1] ) || time() - (int) $token[0] < SAMMAV_MIN_SECONDS ) {
		return $fail( array(), $spam_message );
	}
	$rate_key = 'sammav_rate_' . md5( sammav_client_ip() );
	$count    = (int) get_transient( $rate_key );
	if ( $count >= SAMMAV_RATE_LIMIT ) {
		return $fail( array(), __( 'Too many messages from your connection. Please wait a few minutes and try again.', 'sammav-core' ) );
	}
	if ( ! sammav_turnstile_ok( (string) ( $input['cf-turnstile-response'] ?? '' ) ) ) {
		return $fail( array(), __( 'Please complete the security check and try again.', 'sammav-core' ) );
	}

	// Field validation.
	$errors = array();
	if ( strlen( $data['name'] ) < 2 ) {
		$errors['sv_name'] = __( 'Please enter your full name.', 'sammav-core' );
	}
	if ( ! is_email( $data['email'] ) ) {
		$errors['sv_email'] = __( 'Please enter a valid email address.', 'sammav-core' );
	}
	if ( '' !== $data['phone'] && ! preg_match( '/^[0-9 +()\-]{7,20}$/', $data['phone'] ) ) {
		$errors['sv_phone'] = __( 'Please enter a valid phone number.', 'sammav-core' );
	}
	if ( '' !== $data['service'] && ! in_array( $data['service'], sammav_service_names(), true ) ) {
		$data['service'] = __( 'Other', 'sammav-core' );
	}
	if ( ! array_key_exists( $data['budget'], sammav_budget_options() ) ) {
		$data['budget'] = '';
	}
	if ( strlen( $data['message'] ) < 10 ) {
		$errors['sv_message'] = __( 'Please tell us a little more (at least 10 characters).', 'sammav-core' );
	}
	if ( ! $data['consent'] ) {
		$errors['sv_consent'] = __( 'Please agree so we can respond to your enquiry.', 'sammav-core' );
	}
	if ( $errors ) {
		return $fail( $errors );
	}

	set_transient( $rate_key, $count + 1, 10 * MINUTE_IN_SECONDS );

	$data['budget_label'] = sammav_budget_options()[ $data['budget'] ];
	sammav_store_enquiry( $data );
	sammav_send_enquiry_emails( $data );

	return array(
		'ok'      => true,
		'errors'  => array(),
		'message' => __( 'Thank you! Your message has been sent. We will get back to you within one business day.', 'sammav-core' ),
	);
}

function sammav_turnstile_ok( string $response ): bool {
	$secret = sammav_option( 'turnstile_secret' );
	if ( '' === $secret || '' === sammav_option( 'turnstile_site_key' ) ) {
		return true; // Turnstile not configured.
	}
	if ( '' === $response ) {
		return false;
	}
	$result = wp_remote_post(
		'https://challenges.cloudflare.com/turnstile/v0/siteverify',
		array(
			'timeout' => 8,
			'body'    => array(
				'secret'   => $secret,
				'response' => $response,
				'remoteip' => sammav_client_ip(),
			),
		)
	);
	if ( is_wp_error( $result ) ) {
		return false;
	}
	$body = json_decode( wp_remote_retrieve_body( $result ), true );
	return ! empty( $body['success'] );
}

function sammav_store_enquiry( array $data ): int {
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'sv_enquiry',
			'post_status' => 'private',
			'post_title'  => $data['name'] . ( $data['service'] ? ' — ' . $data['service'] : '' ),
		)
	);
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return 0;
	}
	foreach ( array( 'name', 'email', 'phone', 'company', 'service', 'budget_label', 'message' ) as $key ) {
		update_post_meta( $post_id, 'sv_enquiry_' . $key, $data[ $key ] );
	}
	update_post_meta( $post_id, 'sv_enquiry_consent', gmdate( 'c' ) );
	return (int) $post_id;
}

function sammav_send_enquiry_emails( array $data ): void {
	$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$to    = sammav_option( 'enquiry_email', get_option( 'admin_email' ) );
	$lines = array(
		'Name'    => $data['name'],
		'Email'   => $data['email'],
		'Phone'   => $data['phone'],
		'Company' => $data['company'],
		'Service' => $data['service'],
		'Budget'  => $data['budget_label'],
	);
	$body = '';
	foreach ( $lines as $label => $value ) {
		$body .= $label . ': ' . ( '' !== $value ? $value : '—' ) . "\n";
	}
	$body .= "\nMessage:\n" . $data['message'] . "\n\n— Sent from the contact form at " . home_url( '/' );

	wp_mail(
		$to,
		/* translators: 1: site name, 2: visitor name */
		sprintf( __( '[%1$s] New enquiry from %2$s', 'sammav-core' ), $site, $data['name'] ),
		$body,
		array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' )
	);

	$reply = sprintf(
		/* translators: 1: visitor name, 2: site name, 3: phone */
		__( "Hi %1\$s,\n\nThank you for contacting %2\$s. We have received your message and a member of our team will get back to you within one business day.\n\nIf your request is urgent, call us on %3\$s.\n\nKind regards,\n%2\$s", 'sammav-core' ),
		$data['name'],
		$site,
		sammav_setting( 'phone' )
	);
	wp_mail(
		$data['email'],
		/* translators: %s: site name */
		sprintf( __( 'We received your message — %s', 'sammav-core' ), $site ),
		$reply,
		array( 'Reply-To: ' . $site . ' <' . $to . '>' )
	);
}

/**
 * REST endpoint used by the JS form.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'sammav/v1',
			'/enquiry',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					$result = sammav_process_enquiry( $request->get_body_params() );
					return new WP_REST_Response( $result, $result['ok'] ? 200 : 422 );
				},
			)
		);
	}
);

/**
 * No-JS fallback: process, then redirect back with the outcome.
 */
function sammav_handle_enquiry_post(): void {
	$result   = sammav_process_enquiry( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$referer  = wp_get_referer() ?: home_url( '/contact-us/' );
	$referer  = remove_query_arg( array( 'sv_sent', 'sv_state' ), $referer );
	if ( $result['ok'] ) {
		wp_safe_redirect( add_query_arg( 'sv_sent', '1', $referer ) . '#contact-form' );
		exit;
	}
	$state = wp_generate_password( 12, false );
	set_transient(
		'sammav_form_' . $state,
		array(
			'result' => $result,
			'old'    => array_intersect_key( wp_unslash( $_POST ), array_flip( array( 'sv_name', 'sv_email', 'sv_phone', 'sv_company', 'sv_service', 'sv_budget', 'sv_message' ) ) ), // phpcs:ignore
		),
		10 * MINUTE_IN_SECONDS
	);
	wp_safe_redirect( add_query_arg( 'sv_state', $state, $referer ) . '#contact-form' );
	exit;
}
add_action( 'admin_post_nopriv_sammav_enquiry', 'sammav_handle_enquiry_post' );
add_action( 'admin_post_sammav_enquiry', 'sammav_handle_enquiry_post' );

/**
 * NFR-9: delete enquiries older than the retention period (default 12 months).
 */
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'sammav_purge_enquiries' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sammav_purge_enquiries' );
		}
	}
);
add_action(
	'sammav_purge_enquiries',
	static function () {
		$months = max( 1, (int) sammav_option( 'retention_months', '12' ) );
		$old    = get_posts(
			array(
				'post_type'      => 'sv_enquiry',
				'post_status'    => 'any',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'date_query'     => array( array( 'before' => "-{$months} months" ) ),
			)
		);
		foreach ( $old as $id ) {
			wp_delete_post( $id, true );
		}
	}
);
