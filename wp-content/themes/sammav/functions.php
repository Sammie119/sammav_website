<?php
/**
 * Sammav theme setup.
 */

defined( 'ABSPATH' ) || exit;

define( 'SAMMAV_THEME_VERSION', '1.0.0' );

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/site.css' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'post-thumbnails' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$dir = get_theme_file_path( 'assets' );
		wp_enqueue_style( 'sammav', get_theme_file_uri( 'assets/css/site.css' ), array(), (string) filemtime( "$dir/css/site.css" ) );
		wp_enqueue_script( 'sammav', get_theme_file_uri( 'assets/js/site.js' ), array(), (string) filemtime( "$dir/js/site.js" ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script(
			'sammav',
			'sammavSite',
			array( 'ga4' => function_exists( 'sammav_setting' ) ? sammav_setting( 'ga4_id' ) : '' )
		);
	}
);

/**
 * Highlight the active menu item (FR-G2), including /projects/ on single project pages.
 * Works for custom links too, which core only marks when the link stores a post ID.
 */
add_filter(
	'render_block_core/navigation-link',
	static function ( string $html, array $block ) {
		$url = $block['attrs']['url'] ?? '';
		if ( '' === $url ) {
			return $html;
		}
		$link_path    = trailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$request_path = trailingslashit( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ) );
		$is_current   = '/' === $link_path ? '/' === $request_path : str_starts_with( $request_path, $link_path );
		if ( ! $is_current || str_contains( $html, 'current-menu-item' ) ) {
			return $html;
		}
		$html = preg_replace( '/class="wp-block-navigation-item /', 'class="wp-block-navigation-item current-menu-item ', $html, 1 );
		return preg_replace( '/<a /', '<a aria-current="page" ', $html, 1 );
	},
	10,
	2
);

/**
 * Floating WhatsApp button (FR-G7) and cookie consent banner (FR-G9).
 */
add_action(
	'wp_footer',
	static function () {
		$whatsapp = function_exists( 'sammav_setting' ) ? sammav_setting( 'whatsapp' ) : '';
		if ( $whatsapp ) {
			printf(
				'<a class="sv-whatsapp" href="%1$s" target="_blank" rel="noopener" aria-label="%2$s"><svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16 3a13 13 0 0 0-11.2 19.6L3 29l6.6-1.7A13 13 0 1 0 16 3zm0 23.7c-2 0-4-.6-5.7-1.6l-.4-.2-3.9 1 1-3.8-.3-.4A10.7 10.7 0 1 1 16 26.7zm5.9-8c-.3-.2-1.9-1-2.2-1s-.5-.2-.7.1-.8 1-1 1.2-.4.2-.7 0a8.8 8.8 0 0 1-4.4-3.8c-.3-.6.3-.5 1-1.7.1-.2 0-.4 0-.5l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.9c2 .9 2.8 1 3.9.8a3.3 3.3 0 0 0 2.1-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.2-.3-.3-.6-.4z"/></svg></a>',
				esc_url( 'https://wa.me/' . $whatsapp ),
				esc_attr__( 'Chat with us on WhatsApp', 'sammav' )
			);
		}
		?>
		<div class="sv-cookie" role="region" aria-label="<?php esc_attr_e( 'Cookie consent', 'sammav' ); ?>" hidden>
			<p>
				<?php esc_html_e( 'We use cookies to understand how visitors use our site and improve it.', 'sammav' ); ?>
				<a href="<?php echo esc_url( get_privacy_policy_url() ?: home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'sammav' ); ?></a>
			</p>
			<div class="sv-cookie__actions">
				<button type="button" class="sv-button sv-button--outline" data-consent="no"><?php esc_html_e( 'Decline', 'sammav' ); ?></button>
				<button type="button" class="sv-button" data-consent="yes"><?php esc_html_e( 'Accept', 'sammav' ); ?></button>
			</div>
		</div>
		<?php
	}
);

/**
 * LocalBusiness structured data (NFR-11).
 */
add_action(
	'wp_head',
	static function () {
		if ( ! is_front_page() || ! function_exists( 'sammav_setting' ) ) {
			return;
		}
		$same_as = array_values( array_filter( array_map( 'sammav_setting', array( 'facebook', 'linkedin', 'x', 'instagram' ) ) ) );
		$data    = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'ProfessionalService',
			'name'         => get_bloginfo( 'name' ),
			'description'  => get_bloginfo( 'description' ),
			'url'          => home_url( '/' ),
			'telephone'    => sammav_setting( 'phone' ),
			'email'        => sammav_setting( 'email' ),
			'address'      => array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => sammav_setting( 'address' ),
				'addressCountry' => 'GH',
			),
			'openingHours' => sammav_setting( 'hours' ),
		);
		if ( $same_as ) {
			$data['sameAs'] = $same_as;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
	}
);

/**
 * Meta description fallback until an SEO plugin is installed (NFR-11).
 */
add_action(
	'wp_head',
	static function () {
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$description = get_bloginfo( 'description' );
		if ( is_singular() && has_excerpt() ) {
			$description = get_the_excerpt();
		} elseif ( is_post_type_archive( 'sv_project' ) ) {
			$description = __( 'Selected networking, software, web and infrastructure projects delivered by Sammav IT Services.', 'sammav' );
		}
		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $description ) ) );
		}
	},
	1
);

/**
 * Hardening and trimming (NFR-3, NFR-8).
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', static fn( array $headers ) => array_diff_key( $headers, array( 'X-Pingback' => true ) ) );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * Serve generated image sizes as WebP when the server supports it (NFR-3).
 */
add_filter(
	'image_editor_output_format',
	static function ( array $formats ) {
		if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$formats['image/jpeg'] = 'image/webp';
			$formats['image/png']  = 'image/webp';
		}
		return $formats;
	}
);

/**
 * Pattern category.
 */
add_action(
	'init',
	static function () {
		register_block_pattern_category( 'sammav', array( 'label' => __( 'Sammav', 'sammav' ) ) );
		register_block_style( 'core/button', array( 'name' => 'light', 'label' => __( 'Light', 'sammav' ) ) );
		register_block_style( 'core/button', array( 'name' => 'outline-light', 'label' => __( 'Outline light', 'sammav' ) ) );
	}
);
