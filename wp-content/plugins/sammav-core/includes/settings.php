<?php
/**
 * Settings → Sammav: contact details, social links, integrations.
 * Values are used by the theme (footer, contact page, schema) via [sammav] shortcodes
 * and by the contact form.
 */

defined( 'ABSPATH' ) || exit;

function sammav_settings_fields(): array {
	return array(
		'contact'      => array(
			'title'  => __( 'Contact details', 'sammav-core' ),
			'fields' => array(
				'phone'          => array( __( 'Phone', 'sammav-core' ), '+233 20 000 0000' ),
				'email'          => array( __( 'Public email', 'sammav-core' ), 'info@sammav.example' ),
				'enquiry_email'  => array( __( 'Send enquiries to', 'sammav-core' ), '' ),
				'address'        => array( __( 'Address', 'sammav-core' ), 'Accra, Ghana' ),
				'hours'          => array( __( 'Business hours', 'sammav-core' ), 'Mon – Fri, 8:00 – 17:00' ),
				'whatsapp'       => array( __( 'WhatsApp number (digits only, with country code)', 'sammav-core' ), '233200000000' ),
				'map_query'      => array( __( 'Google Maps search (address or place name)', 'sammav-core' ), 'Accra, Ghana' ),
			),
		),
		'social'       => array(
			'title'  => __( 'Social links', 'sammav-core' ),
			'fields' => array(
				'facebook' => array( 'Facebook URL', '' ),
				'linkedin' => array( 'LinkedIn URL', '' ),
				'x'        => array( 'X (Twitter) URL', '' ),
				'instagram' => array( 'Instagram URL', '' ),
			),
		),
		'integrations' => array(
			'title'  => __( 'Integrations and privacy', 'sammav-core' ),
			'fields' => array(
				'ga4_id'             => array( __( 'Google Analytics 4 ID (G-XXXX). Loaded only after cookie consent.', 'sammav-core' ), '' ),
				'turnstile_site_key' => array( __( 'Cloudflare Turnstile site key', 'sammav-core' ), '' ),
				'turnstile_secret'   => array( __( 'Cloudflare Turnstile secret key', 'sammav-core' ), '' ),
				'retention_months'   => array( __( 'Delete enquiries after (months)', 'sammav-core' ), '12' ),
			),
		),
	);
}

/**
 * Value with its default, for display.
 */
function sammav_setting( string $key ): string {
	foreach ( sammav_settings_fields() as $section ) {
		if ( isset( $section['fields'][ $key ] ) ) {
			return sammav_option( $key, $section['fields'][ $key ][1] );
		}
	}
	return sammav_option( $key );
}

add_action(
	'admin_menu',
	static function () {
		add_options_page(
			__( 'Sammav settings', 'sammav-core' ),
			__( 'Sammav', 'sammav-core' ),
			'manage_options',
			'sammav',
			static function () {
				echo '<div class="wrap"><h1>' . esc_html__( 'Sammav settings', 'sammav-core' ) . '</h1><form method="post" action="options.php">';
				settings_fields( 'sammav' );
				do_settings_sections( 'sammav' );
				submit_button();
				echo '</form></div>';
			}
		);
	}
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			'sammav',
			'sammav_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $input ) {
					$clean = array();
					foreach ( sammav_settings_fields() as $section ) {
						foreach ( array_keys( $section['fields'] ) as $key ) {
							$value = isset( $input[ $key ] ) ? trim( wp_unslash( (string) $input[ $key ] ) ) : '';
							if ( in_array( $key, array( 'facebook', 'linkedin', 'x', 'instagram' ), true ) ) {
								$value = esc_url_raw( $value );
							} elseif ( in_array( $key, array( 'email', 'enquiry_email' ), true ) ) {
								$value = sanitize_email( $value );
							} elseif ( 'whatsapp' === $key || 'retention_months' === $key ) {
								$value = preg_replace( '/\D/', '', $value );
							} else {
								$value = sanitize_text_field( $value );
							}
							$clean[ $key ] = $value;
						}
					}
					return $clean;
				},
			)
		);

		foreach ( sammav_settings_fields() as $section_id => $section ) {
			add_settings_section( $section_id, $section['title'], '__return_null', 'sammav' );
			foreach ( $section['fields'] as $key => [ $label, $default ] ) {
				add_settings_field(
					$key,
					esc_html( $label ),
					static function () use ( $key, $default ) {
						$type = 'turnstile_secret' === $key ? 'password' : 'text';
						printf(
							'<input type="%1$s" class="regular-text" name="sammav_settings[%2$s]" value="%3$s" placeholder="%4$s">',
							esc_attr( $type ),
							esc_attr( $key ),
							esc_attr( sammav_option( $key ) ),
							esc_attr( $default )
						);
					},
					'sammav',
					$section_id
				);
			}
		}
	}
);

/**
 * [sammav field="phone" link="tel"] — prints a setting, optionally as a link.
 * Used in the theme's footer and contact patterns so details live in one place.
 */
add_shortcode(
	'sammav',
	static function ( $atts ) {
		$atts  = shortcode_atts(
			array(
				'field' => '',
				'link'  => '',
				'icon'  => '',
			),
			$atts
		);
		$value = sammav_setting( sanitize_key( $atts['field'] ) );
		if ( '' === $value ) {
			return '';
		}
		$icon = $atts['icon'] ? sammav_icon( sanitize_key( $atts['icon'] ) ) . ' ' : '';
		switch ( $atts['link'] ) {
			case 'tel':
				return $icon . '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $value ) ) . '">' . esc_html( $value ) . '</a>';
			case 'mailto':
				return $icon . '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
			case 'whatsapp':
				return $icon . '<a href="https://wa.me/' . esc_attr( $value ) . '" target="_blank" rel="noopener">' . esc_html__( 'Chat on WhatsApp', 'sammav-core' ) . '</a>';
			case 'map':
				return '<iframe class="sv-map" title="' . esc_attr__( 'Map showing our office location', 'sammav-core' ) . '" src="https://maps.google.com/maps?q=' . rawurlencode( $value ) . '&amp;z=14&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
			default:
				return $icon . esc_html( $value );
		}
	}
);

/**
 * [sammav_social] — social icon links for the settings that are filled in.
 */
add_shortcode(
	'sammav_social',
	static function () {
		$networks = array(
			'facebook'  => 'Facebook',
			'linkedin'  => 'LinkedIn',
			'x'         => 'X',
			'instagram' => 'Instagram',
		);
		$items = '';
		foreach ( $networks as $key => $label ) {
			$url = sammav_setting( $key );
			if ( $url ) {
				$items .= '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a></li>';
			}
		}
		$whatsapp = sammav_setting( 'whatsapp' );
		if ( $whatsapp ) {
			$items .= '<li><a href="https://wa.me/' . esc_attr( $whatsapp ) . '" target="_blank" rel="noopener">WhatsApp</a></li>';
		}
		return $items ? '<ul class="sv-social">' . $items . '</ul>' : '';
	}
);

/**
 * [sammav_year] — current year for the copyright line.
 */
add_shortcode( 'sammav_year', static fn() => esc_html( wp_date( 'Y' ) ) );
