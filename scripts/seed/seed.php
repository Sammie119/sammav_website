<?php
/**
 * Seeds pages, menu and placeholder content. Safe to run more than once:
 * every item is looked up by a stable key before it is created.
 *
 * Run with: wp eval-file /seed/seed.php
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Docker mounts this folder at /seed; elsewhere use the script's own folder.
$seed_dir = is_dir( '/seed/images' ) ? '/seed' : __DIR__;

/** Find a post previously created by this script. */
function sv_seed_find( string $key, string $post_type ): int {
	$found = get_posts(
		array(
			'post_type'   => $post_type,
			'post_status' => 'any',
			'meta_key'    => '_sv_seed', // phpcs:ignore
			'meta_value'  => $key, // phpcs:ignore
			'fields'      => 'ids',
			'numberposts' => 1,
		)
	);
	return $found ? (int) $found[0] : 0;
}

function sv_seed_post( string $key, array $args, array $meta = array() ): int {
	$id = sv_seed_find( $key, $args['post_type'] );
	if ( $id ) {
		WP_CLI::log( "  exists: {$args['post_title']}" );
		return $id;
	}
	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	$id     = wp_insert_post( array_merge( array( 'post_status' => 'publish', 'post_author' => (int) ( $admins[0] ?? 0 ) ), $args ), true );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	update_post_meta( $id, '_sv_seed', $key );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	WP_CLI::log( "  created: {$args['post_title']}" );
	return (int) $id;
}

function sv_seed_image( string $file, string $alt, int $parent = 0 ): int {
	$key = 'img:' . basename( $file );
	$id  = sv_seed_find( $key, 'attachment' );
	if ( $id ) {
		return $id;
	}
	$tmp = wp_tempnam( basename( $file ) );
	copy( $file, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => basename( $file ),
			'tmp_name' => $tmp,
		),
		$parent,
		$alt
	);
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_sv_seed', $key );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}

/** Expand <!-- wp:pattern {"slug":"x"} /--> blocks into the pattern's markup so pages are fully editable. */
function sv_expand_patterns( string $content, int $depth = 0 ): string {
	if ( $depth > 5 ) {
		return $content;
	}
	return preg_replace_callback(
		'/<!-- wp:pattern \{"slug":"([^"]+)"\} \/-->/',
		static function ( $m ) use ( $depth ) {
			$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $m[1] );
			return $pattern ? sv_expand_patterns( $pattern['content'], $depth + 1 ) : '';
		},
		$content
	);
}

function sv_pattern( string $slug ): string {
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
	if ( ! $pattern ) {
		WP_CLI::error( "Pattern $slug is not registered. Is the sammav theme active?" );
	}
	return sv_expand_patterns( $pattern['content'] );
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Pages' );

$home    = sv_seed_post( 'page:home', array( 'post_type' => 'page', 'post_title' => 'Home', 'post_name' => 'home', 'post_content' => sv_pattern( 'sammav/page-home' ), 'post_excerpt' => 'Sammav IT Services designs, builds and supports networks, software, websites and cloud systems for organisations in Ghana.' ) );
$about   = sv_seed_post( 'page:about', array( 'post_type' => 'page', 'post_title' => 'About Us', 'post_name' => 'about-us', 'post_content' => sv_pattern( 'sammav/page-about' ), 'post_excerpt' => 'Meet Sammav IT Services: an Accra-based team delivering networking, software, web, cloud and cybersecurity services since 2018.', 'menu_order' => 2 ), array( '_wp_page_template' => 'page-landing' ) );
$contact = sv_seed_post( 'page:contact', array( 'post_type' => 'page', 'post_title' => 'Contact Us', 'post_name' => 'contact-us', 'post_content' => sv_pattern( 'sammav/page-contact' ), 'post_excerpt' => 'Contact Sammav IT Services for a quote or support: call, email, WhatsApp or send us a message.', 'menu_order' => 3 ), array( '_wp_page_template' => 'page-landing' ) );

$privacy_content = <<<'HTML'
<!-- wp:paragraph -->
<p>This policy explains what personal data Sammav IT Services collects through this website and how we use it. <strong>Placeholder text: have it reviewed before launch.</strong></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">What we collect</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>When you use our contact form we collect your name, email address and, if you provide them, your phone number, company, service of interest, budget range and message. If you accept analytics cookies, Google Analytics collects anonymised usage data.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How we use it</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We use your details only to respond to your enquiry and, if you become a client, to deliver our services. We do not sell or share your data with third parties for marketing.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">How long we keep it</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Enquiries are deleted automatically after 12 months unless they lead to a contract.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Your rights</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Under the Ghana Data Protection Act, 2012 (Act 843) you may ask to see, correct or delete the personal data we hold about you. Contact us using the details on our Contact Us page.</p>
<!-- /wp:paragraph -->
HTML;
// WordPress creates a draft "Privacy Policy" page on install; take it over rather than duplicate it.
$core_privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
if ( $core_privacy && ! get_post_meta( $core_privacy->ID, '_sv_seed', true ) ) {
	wp_update_post(
		array(
			'ID'           => $core_privacy->ID,
			'post_status'  => 'publish',
			'post_content' => $privacy_content,
			'menu_order'   => 9,
		)
	);
	update_post_meta( $core_privacy->ID, '_sv_seed', 'page:privacy' );
}
$privacy = sv_seed_post( 'page:privacy', array( 'post_type' => 'page', 'post_title' => 'Privacy Policy', 'post_name' => 'privacy-policy', 'post_content' => $privacy_content, 'menu_order' => 9 ) );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );
update_option( 'wp_page_for_privacy_policy', $privacy );

// Remove the default sample content.
foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
	$post = get_page_by_path( $slug, OBJECT, $type );
	if ( $post ) {
		wp_delete_post( $post->ID, true );
	}
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Navigation' );

$nav_links = array(
	array( 'Home', '/', 'page', $home ),
	array( 'Projects', '/projects/', 'custom', 0 ),
	array( 'About Us', '/about-us/', 'page', $about ),
	array( 'Contact Us', '/contact-us/', 'page', $contact ),
);
$nav_content = '';
foreach ( $nav_links as [ $label, $url, $kind, $id ] ) {
	$attrs = array(
		'label' => $label,
		'url'   => home_url( $url ),
		'kind'  => 'page' === $kind ? 'post-type' : 'custom',
	);
	if ( $id ) {
		$attrs['type'] = 'page';
		$attrs['id']   = $id;
	}
	$nav_content .= '<!-- wp:navigation-link ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . " /-->\n";
}
$nav_id = sv_seed_find( 'nav:primary', 'wp_navigation' );
if ( $nav_id ) {
	wp_update_post( array( 'ID' => $nav_id, 'post_content' => $nav_content ) );
	WP_CLI::log( '  updated: Primary navigation' );
} else {
	sv_seed_post( 'nav:primary', array( 'post_type' => 'wp_navigation', 'post_title' => 'Primary', 'post_content' => $nav_content ) );
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Settings' );

if ( ! get_option( 'sammav_settings' ) ) {
	update_option(
		'sammav_settings',
		array(
			'phone'     => '+233 20 000 0000',
			'email'     => 'info@sammav.example',
			'address'   => 'Ring Road Central, Accra, Ghana',
			'hours'     => 'Mon – Fri, 8:00 – 17:00',
			'whatsapp'  => '233200000000',
			'map_query' => 'Ring Road Central, Accra, Ghana',
			'facebook'  => 'https://www.facebook.com/',
			'linkedin'  => 'https://www.linkedin.com/',
			'retention_months' => '12',
		)
	);
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Services' );

$services = array(
	array( 'Networking & Infrastructure', 'network', 'Structured cabling, LAN/WAN, Wi-Fi and server rooms designed for speed, security and growth.' ),
	array( 'Software Development', 'code', 'Custom business applications, integrations and mobile apps built around how your team works.' ),
	array( 'Web Design & Development', 'web', 'Fast, accessible websites and web apps that turn visitors into customers.' ),
	array( 'IT Support & Maintenance', 'support', 'Help desk, on-site support and proactive maintenance with clear response times.' ),
	array( 'Cloud & Hosting', 'cloud', 'Microsoft 365, Google Workspace, AWS and managed hosting with backups you can rely on.' ),
	array( 'Cybersecurity', 'shield', 'Firewalls, endpoint protection, security audits and staff awareness training.' ),
);
foreach ( $services as $i => [ $title, $icon, $text ] ) {
	sv_seed_post( 'service:' . sanitize_title( $title ), array( 'post_type' => 'sv_service', 'post_title' => $title, 'post_content' => $text, 'menu_order' => $i ), array( 'sv_icon' => $icon ) );
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Projects' );

$portfolio = require $seed_dir . '/projects.php';

// Remove the original placeholder projects (and their images) from earlier versions of this seed.
$placeholder_keys = array(
	'project:branch-network-upgrade-for-gold-coast-bank',
	'project:patient-records-system-for-volta-health',
	'project:e-commerce-website-for-acme-logistics',
	'project:campus-wi-fi-for-kumasi-schools',
	'project:cloud-migration-for-a-financial-services-firm',
	'project:inventory-app-for-tema-port-co',
);
foreach ( $placeholder_keys as $old_key ) {
	$old_id = sv_seed_find( $old_key, 'sv_project' );
	if ( ! $old_id ) {
		continue;
	}
	foreach ( get_attached_media( 'image', $old_id ) as $media ) {
		wp_delete_attachment( $media->ID, true );
	}
	wp_delete_post( $old_id, true );
	WP_CLI::log( "  removed placeholder: $old_key" );
}

$cats = array();
foreach ( $portfolio['categories'] as $name ) {
	$term = term_exists( $name, 'sv_project_category' ) ?: wp_insert_term( $name, 'sv_project_category' );
	$cats[ $name ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
}

// Create or update every project so edits in projects.php reach the site on re-run.
foreach ( $portfolio['projects'] as $i => $p ) {
	$key  = 'project:' . $p['key'];
	$id   = sv_seed_find( $key, 'sv_project' );
	$date = $p['date'] ?: gmdate( 'Y-m-d' );
	$post = array(
		'post_type'    => 'sv_project',
		'post_title'   => $p['title'],
		'post_name'    => sanitize_title( $p['key'] ),
		'post_excerpt' => $p['summary'],
		'post_content' => "<!-- wp:paragraph -->\n<p>" . esc_html( $p['overview'] ) . "</p>\n<!-- /wp:paragraph -->",
		'post_date'    => $date . ' 09:00:00',
		'menu_order'   => (int) $p['order'],
	);
	if ( $id ) {
		wp_update_post( array_merge( $post, array( 'ID' => $id ) ) );
		WP_CLI::log( "  updated: {$p['title']}" );
	} else {
		$id = sv_seed_post( $key, $post );
	}
	$meta = array(
		'sv_featured'     => $p['featured'] ? '1' : '',
		'sv_client'       => $p['client'],
		'sv_hide_client'  => '',
		'sv_completed'    => $p['date'],
		'sv_summary'      => $p['summary'],
		'sv_challenge'    => $p['challenge'],
		'sv_solution'     => $p['solution'],
		'sv_results'      => $p['results'],
		'sv_technologies' => $p['tech'],
		'sv_testimonial'  => '',
		'sv_gallery'      => '',
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	wp_set_object_terms( $id, array( $cats[ $p['cat'] ] ), 'sv_project_category' );

	if ( ! has_post_thumbnail( $id ) ) {
		$cover = sv_seed_image( "$seed_dir/images/cover-{$p['key']}.jpg", $p['title'], $id );
		if ( $cover ) {
			set_post_thumbnail( $id, $cover );
		}
	}
}

// Remove categories no longer used (names are stored HTML-encoded, e.g. "&amp;").
foreach ( get_terms( array( 'taxonomy' => 'sv_project_category', 'hide_empty' => false ) ) as $term ) {
	if ( ! in_array( html_entity_decode( $term->name ), $portfolio['categories'], true ) && 0 === (int) $term->count ) {
		wp_delete_term( $term->term_id, 'sv_project_category' );
	}
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Testimonials' );

$testimonials = array(
	array( 'Kwame Asante', 'IT Manager, Gold Coast Bank', 'Sammav planned every branch upgrade around our opening hours. Downtime is now a rare event instead of a weekly one.' ),
	array( 'Abena Mensah', 'Operations Director, Acme Logistics', 'Our new website books shipments while we sleep. Sammav understood our business and delivered on time and on budget.' ),
	array( 'Dr. Yaw Owusu', 'Medical Director, Volta Health', 'The records system has transformed how our clinics work together. Their support team responds within minutes.' ),
);
foreach ( $testimonials as $i => [ $name, $company, $quote ] ) {
	sv_seed_post( 'testimonial:' . sanitize_title( $name ), array( 'post_type' => 'sv_testimonial', 'post_title' => $name, 'post_content' => $quote, 'menu_order' => $i ), array( 'sv_person' => $name, 'sv_company' => $company ) );
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Team' );

$team = array(
	array( 'Samuel Duah', 'Founder & Managing Director', '15+ years in enterprise networking and IT strategy. Cisco and Microsoft certified.' ),
	array( 'Ama Mensah', 'Head of Software Development', 'Leads our development team building web, mobile and business applications.' ),
	array( 'Kofi Ofori', 'Network & Security Engineer', 'Designs and secures networks for banks, schools and healthcare providers.' ),
	array( 'Esi Addo', 'Client Success Manager', 'Keeps projects on track and makes sure every client gets the support they need.' ),
);
foreach ( $team as $i => [ $name, $role, $bio ] ) {
	$id = sv_seed_post( 'team:' . sanitize_title( $name ), array( 'post_type' => 'sv_team', 'post_title' => $name, 'post_content' => $bio, 'menu_order' => $i ), array( 'sv_role' => $role, 'sv_linkedin' => 'https://www.linkedin.com/' ) );
	if ( ! has_post_thumbnail( $id ) ) {
		$photo = sv_seed_image( "$seed_dir/images/team-" . ( $i + 1 ) . '.jpg', "Photo of $name", $id );
		if ( $photo ) {
			set_post_thumbnail( $id, $photo );
		}
	}
}

WP_CLI::success( 'Seed complete.' );
