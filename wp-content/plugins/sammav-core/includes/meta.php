<?php
/**
 * Custom fields for each content type: registration, meta boxes and saving.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions per post type: key => [label, type, help].
 * Types: text, textarea, url, date, checkbox, gallery, icon.
 */
function sammav_meta_fields(): array {
	return array(
		'sv_project'     => array(
			'sv_featured'     => array( __( 'Feature on Home page', 'sammav-core' ), 'checkbox', __( 'The 3 most recent featured projects appear on the Home page.', 'sammav-core' ) ),
			'sv_client'       => array( __( 'Client name', 'sammav-core' ), 'text', '' ),
			'sv_hide_client'  => array( __( 'Hide client name (confidential)', 'sammav-core' ), 'checkbox', '' ),
			'sv_completed'    => array( __( 'Completion date', 'sammav-core' ), 'date', '' ),
			'sv_summary'      => array( __( 'Short summary', 'sammav-core' ), 'textarea', __( 'One or two sentences shown on project cards.', 'sammav-core' ) ),
			'sv_challenge'    => array( __( 'The challenge', 'sammav-core' ), 'textarea', '' ),
			'sv_solution'     => array( __( 'Our solution', 'sammav-core' ), 'textarea', '' ),
			'sv_results'      => array( __( 'Results', 'sammav-core' ), 'textarea', __( 'One result per line.', 'sammav-core' ) ),
			'sv_technologies' => array( __( 'Technologies used', 'sammav-core' ), 'text', __( 'Comma separated, e.g. WordPress, Cisco, AWS.', 'sammav-core' ) ),
			'sv_live_url'     => array( __( 'Live URL (optional)', 'sammav-core' ), 'url', '' ),
			'sv_gallery'      => array( __( 'Image gallery', 'sammav-core' ), 'gallery', '' ),
			'sv_testimonial'  => array( __( 'Client testimonial (optional)', 'sammav-core' ), 'textarea', '' ),
		),
		'sv_service'     => array(
			'sv_icon' => array( __( 'Icon', 'sammav-core' ), 'icon', '' ),
		),
		'sv_testimonial' => array(
			'sv_person'  => array( __( 'Person name', 'sammav-core' ), 'text', '' ),
			'sv_company' => array( __( 'Role and company', 'sammav-core' ), 'text', '' ),
		),
		'sv_team'        => array(
			'sv_role'     => array( __( 'Role', 'sammav-core' ), 'text', '' ),
			'sv_linkedin' => array( __( 'LinkedIn URL', 'sammav-core' ), 'url', '' ),
		),
	);
}

add_action(
	'init',
	static function () {
		foreach ( sammav_meta_fields() as $post_type => $fields ) {
			foreach ( $fields as $key => [ , $type ] ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => 'checkbox' === $type ? 'boolean' : 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => static fn( $value ) => sammav_sanitize_meta( $value, $type ),
						'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
					)
				);
			}
		}
	}
);

function sammav_sanitize_meta( $value, string $type ) {
	switch ( $type ) {
		case 'checkbox':
			return (bool) $value;
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		case 'url':
			return esc_url_raw( (string) $value );
		case 'date':
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? (string) $value : '';
		case 'gallery':
			return implode( ',', array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) );
		case 'icon':
			return array_key_exists( (string) $value, sammav_icon_choices() ) ? (string) $value : 'code';
		default:
			return sanitize_text_field( (string) $value );
	}
}

add_action(
	'add_meta_boxes',
	static function () {
		$titles = array(
			'sv_project'     => __( 'Project details', 'sammav-core' ),
			'sv_service'     => __( 'Service details', 'sammav-core' ),
			'sv_testimonial' => __( 'Testimonial details', 'sammav-core' ),
			'sv_team'        => __( 'Team member details', 'sammav-core' ),
		);
		foreach ( $titles as $post_type => $title ) {
			add_meta_box( 'sammav_fields', $title, 'sammav_render_meta_box', $post_type, 'normal', 'high' );
		}
	}
);

function sammav_render_meta_box( WP_Post $post ): void {
	$fields = sammav_meta_fields()[ $post->post_type ] ?? array();
	wp_nonce_field( 'sammav_save_meta', 'sammav_meta_nonce' );
	echo '<div class="sammav-fields">';
	foreach ( $fields as $key => [ $label, $type, $help ] ) {
		$value = get_post_meta( $post->ID, $key, true );
		$id    = esc_attr( $key );
		echo '<p class="sammav-field sammav-field--' . esc_attr( $type ) . '">';
		switch ( $type ) {
			case 'checkbox':
				printf( '<label><input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s> %3$s</label>', $id, checked( (bool) $value, true, false ), esc_html( $label ) );
				break;
			case 'textarea':
				printf( '<label for="%1$s"><strong>%2$s</strong></label><br><textarea class="widefat" rows="4" name="%1$s" id="%1$s">%3$s</textarea>', $id, esc_html( $label ), esc_textarea( (string) $value ) );
				break;
			case 'icon':
				printf( '<label for="%1$s"><strong>%2$s</strong></label><br><select name="%1$s" id="%1$s">', $id, esc_html( $label ) );
				foreach ( sammav_icon_choices() as $icon => $name ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $icon ), selected( $value, $icon, false ), esc_html( $name ) );
				}
				echo '</select>';
				break;
			case 'gallery':
				printf( '<strong>%s</strong><br>', esc_html( $label ) );
				printf( '<input type="hidden" name="%1$s" id="%1$s" value="%2$s" class="sammav-gallery-input">', $id, esc_attr( (string) $value ) );
				echo '<span class="sammav-gallery-preview">';
				foreach ( array_filter( explode( ',', (string) $value ) ) as $attachment_id ) {
					echo wp_get_attachment_image( (int) $attachment_id, array( 60, 60 ) );
				}
				echo '</span><br><button type="button" class="button sammav-gallery-pick">' . esc_html__( 'Choose images', 'sammav-core' ) . '</button> ';
				echo '<button type="button" class="button-link sammav-gallery-clear">' . esc_html__( 'Clear', 'sammav-core' ) . '</button>';
				break;
			default:
				$input_type = in_array( $type, array( 'url', 'date' ), true ) ? $type : 'text';
				printf( '<label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s" name="%1$s" id="%1$s" value="%4$s">', $id, esc_html( $label ), esc_attr( $input_type ), esc_attr( (string) $value ) );
		}
		if ( $help ) {
			echo '<br><span class="description">' . esc_html( $help ) . '</span>';
		}
		echo '</p>';
	}
	echo '</div>';
}

add_action(
	'save_post',
	static function ( int $post_id, WP_Post $post ) {
		if ( ! isset( $_POST['sammav_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sammav_meta_nonce'] ), 'sammav_save_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( sammav_meta_fields()[ $post->post_type ] ?? array() as $key => [ , $type ] ) {
			$raw = wp_unslash( $_POST[ $key ] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			update_post_meta( $post_id, $key, sammav_sanitize_meta( $raw, $type ) );
		}
	},
	10,
	2
);

add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ) {
		$screen = get_current_screen();
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || 'sv_project' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'sammav-admin-gallery', SAMMAV_CORE_URL . 'assets/admin-gallery.js', array( 'jquery' ), SAMMAV_CORE_VERSION, true );
	}
);
