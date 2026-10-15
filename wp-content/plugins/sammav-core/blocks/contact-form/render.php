<?php
/**
 * Contact form (FR-C1 – C4, C9).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display state.
$sent   = isset( $_GET['sv_sent'] );
$state  = isset( $_GET['sv_state'] ) ? get_transient( 'sammav_form_' . sanitize_key( $_GET['sv_state'] ) ) : false;
$result = $state['result'] ?? null;
$old    = $state['old'] ?? array();
$errors = $result['errors'] ?? array();
if ( empty( $old['sv_service'] ) && isset( $_GET['service'] ) ) {
	$old['sv_service'] = sanitize_text_field( wp_unslash( $_GET['service'] ) );
}
// phpcs:enable

$services = sammav_service_names();
$site_key = sammav_option( 'turnstile_site_key' );
if ( $site_key && sammav_option( 'turnstile_secret' ) ) {
	wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'strategy' => 'defer' ) ); // phpcs:ignore
} else {
	$site_key = '';
}

$value = static fn( string $key ): string => (string) ( $old[ $key ] ?? '' );

$field = static function ( string $name, string $label, string $type = 'text', bool $required = false, string $autocomplete = '' ) use ( $errors, $value ) {
	$id    = 'sv-' . $name;
	$error = $errors[ $name ] ?? '';
	?>
	<div class="sv-field<?php echo $error ? ' has-error' : ''; ?>">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?><?php echo $required ? ' <span class="sv-required" aria-hidden="true">*</span>' : ''; ?></label>
		<?php if ( 'textarea' === $type ) : ?>
			<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="6" minlength="10" <?php echo $required ? 'required aria-required="true"' : ''; ?> aria-describedby="<?php echo esc_attr( $id ); ?>-error" <?php echo $error ? 'aria-invalid="true"' : ''; ?>><?php echo esc_textarea( $value( $name ) ); ?></textarea>
		<?php else : ?>
			<input id="<?php echo esc_attr( $id ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value( $name ) ); ?>" <?php echo $autocomplete ? 'autocomplete="' . esc_attr( $autocomplete ) . '"' : ''; ?> <?php echo $required ? 'required aria-required="true"' : ''; ?> aria-describedby="<?php echo esc_attr( $id ); ?>-error" <?php echo $error ? 'aria-invalid="true"' : ''; ?>>
		<?php endif; ?>
		<p class="sv-field__error" id="<?php echo esc_attr( $id ); ?>-error"><?php echo esc_html( $error ); ?></p>
	</div>
	<?php
};
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sv-contact-form' ) ); ?> id="contact-form">
	<div class="sv-form-status<?php echo $sent ? ' is-success' : ( $result ? ' is-error' : '' ); ?>" role="status" aria-live="polite" tabindex="-1">
		<?php
		if ( $sent ) {
			esc_html_e( 'Thank you! Your message has been sent. We will get back to you within one business day.', 'sammav-core' );
		} elseif ( $result ) {
			echo esc_html( $result['message'] );
		}
		?>
	</div>

	<form class="sv-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-endpoint="<?php echo esc_url( rest_url( 'sammav/v1/enquiry' ) ); ?>" novalidate <?php echo $sent ? 'hidden' : ''; ?>>
		<input type="hidden" name="action" value="sammav_enquiry">
		<input type="hidden" name="sv_token" value="<?php echo esc_attr( sammav_form_token() ); ?>">
		<div class="sv-hp" aria-hidden="true">
			<label for="sv-website">Website</label>
			<input type="text" id="sv-website" name="sv_website" tabindex="-1" autocomplete="off">
		</div>

		<div class="sv-form__row">
			<?php $field( 'sv_name', __( 'Full name', 'sammav-core' ), 'text', true, 'name' ); ?>
			<?php $field( 'sv_email', __( 'Email', 'sammav-core' ), 'email', true, 'email' ); ?>
		</div>
		<div class="sv-form__row">
			<?php $field( 'sv_phone', __( 'Phone', 'sammav-core' ), 'tel', false, 'tel' ); ?>
			<?php $field( 'sv_company', __( 'Company', 'sammav-core' ), 'text', false, 'organization' ); ?>
		</div>
		<div class="sv-form__row">
			<div class="sv-field">
				<label for="sv-sv_service"><?php esc_html_e( 'Service of interest', 'sammav-core' ); ?></label>
				<select id="sv-sv_service" name="sv_service">
					<option value=""><?php esc_html_e( 'Select a service', 'sammav-core' ); ?></option>
					<?php foreach ( $services as $service ) : ?>
						<option value="<?php echo esc_attr( $service ); ?>" <?php selected( strtolower( $value( 'sv_service' ) ), strtolower( $service ) ); ?>><?php echo esc_html( $service ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="sv-field">
				<label for="sv-sv_budget"><?php esc_html_e( 'Budget range', 'sammav-core' ); ?></label>
				<select id="sv-sv_budget" name="sv_budget">
					<?php foreach ( sammav_budget_options() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value( 'sv_budget' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php $field( 'sv_message', __( 'How can we help?', 'sammav-core' ), 'textarea', true ); ?>

		<div class="sv-field sv-field--check<?php echo isset( $errors['sv_consent'] ) ? ' has-error' : ''; ?>">
			<label>
				<input type="checkbox" name="sv_consent" value="1" required aria-required="true" aria-describedby="sv-sv_consent-error">
				<span>
					<?php
					printf(
						/* translators: %s: privacy policy link */
						esc_html__( 'I agree that Sammav IT Services may store my details to respond to this enquiry, as described in the %s.', 'sammav-core' ),
						'<a href="' . esc_url( get_privacy_policy_url() ?: home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'sammav-core' ) . '</a>'
					);
					?>
					<span class="sv-required" aria-hidden="true">*</span>
				</span>
			</label>
			<p class="sv-field__error" id="sv-sv_consent-error"><?php echo esc_html( $errors['sv_consent'] ?? '' ); ?></p>
		</div>

		<?php if ( $site_key ) : ?>
			<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
		<?php endif; ?>

		<p class="sv-form__note"><span aria-hidden="true">*</span> <?php esc_html_e( 'Required fields', 'sammav-core' ); ?></p>
		<button type="submit" class="sv-button sv-button--large"><?php esc_html_e( 'Send message', 'sammav-core' ); ?></button>
	</form>
</div>
