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
WP_CLI::log( 'Project categories' );

$cats = array();
foreach ( array( 'Web', 'Networking', 'Software', 'Infrastructure' ) as $name ) {
	$term = term_exists( $name, 'sv_project_category' ) ?: wp_insert_term( $name, 'sv_project_category' );
	$cats[ $name ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
}

// ---------------------------------------------------------------------------
WP_CLI::log( 'Projects' );

$projects = array(
	array(
		'title'      => 'Branch Network Upgrade for Gold Coast Bank',
		'cat'        => 'Networking',
		'client'     => 'Gold Coast Bank',
		'date'       => '2026-08-20',
		'featured'   => true,
		'summary'    => 'Redesigned the WAN and Wi-Fi for 14 branches, cutting downtime and doubling bandwidth.',
		'overview'   => 'Gold Coast Bank needed a faster, more secure network to support new digital banking services across its branches.',
		'challenge'  => "Branches relied on ageing switches and a flat network, causing frequent outages and slow core-banking sessions during peak hours.",
		'solution'   => "We designed a segmented SD-WAN with redundant links, replaced switching and Wi-Fi in every branch, and set up central monitoring with alerting.",
		'results'    => "Network downtime reduced by 85%\nBandwidth per branch doubled\nAll branches upgraded over weekends with zero business disruption",
		'tech'       => 'Cisco Meraki, SD-WAN, VLANs, Fortinet',
		'testimonial' => 'Sammav planned every branch cut-over around our opening hours. Our staff barely noticed the change, except that everything got faster.',
	),
	array(
		'title'      => 'Patient Records System for Volta Health',
		'cat'        => 'Software',
		'client'     => 'Volta Health',
		'date'       => '2026-05-12',
		'featured'   => true,
		'summary'    => 'A secure web-based patient records and appointments system for a network of five clinics.',
		'overview'   => 'Volta Health wanted to move from paper files to a single digital record shared securely across its clinics.',
		'challenge'  => 'Patient files were kept on paper at each clinic, making referrals slow and records easy to lose.',
		'solution'   => 'We built a role-based records and appointment system with offline support for clinics with unreliable internet, plus SMS reminders for patients.',
		'results'    => "Appointment no-shows down 30%\nRecords available across all five clinics\nStaff trained in two weeks",
		'tech'       => 'Laravel, Vue.js, PostgreSQL, AWS',
		'testimonial' => '',
	),
	array(
		'title'      => 'E-commerce Website for Acme Logistics',
		'cat'        => 'Web',
		'client'     => 'Acme Logistics',
		'date'       => '2026-02-03',
		'featured'   => true,
		'summary'    => 'A fast, mobile-first website with online shipment booking and tracking.',
		'overview'   => 'Acme Logistics needed a modern website that lets customers book and track shipments without calling the office.',
		'challenge'  => 'The old website was slow on mobile and all bookings were made by phone, overloading the customer service team.',
		'solution'   => 'We designed and built a WordPress site with a custom booking and tracking module integrated with their dispatch system and Mobile Money payments.',
		'results'    => "60% of bookings now made online\nPage load under 2 seconds on 4G\nCustomer service calls reduced by 40%",
		'tech'       => 'WordPress, PHP, REST API, Mobile Money',
		'testimonial' => 'Our customers love being able to book and track online. The site paid for itself within months.',
	),
	array(
		'title'      => 'Campus Wi-Fi for Kumasi Schools',
		'cat'        => 'Infrastructure',
		'client'     => 'Kumasi Schools',
		'date'       => '2025-11-15',
		'featured'   => false,
		'summary'    => 'Campus-wide Wi-Fi and content filtering for three secondary schools.',
		'overview'   => 'Three schools needed reliable Wi-Fi for e-learning across classrooms, labs and dormitories.',
		'challenge'  => 'Coverage was patchy, there was no content filtering and the network could not handle hundreds of student devices.',
		'solution'   => 'We surveyed each site, installed managed access points with solar-backed power, and set up content filtering and per-user bandwidth controls.',
		'results'    => "Full coverage across 3 campuses\n1,500+ concurrent devices supported\nSafe browsing for all students",
		'tech'       => 'Ubiquiti UniFi, pfSense, Solar UPS',
		'testimonial' => '',
	),
	array(
		'title'      => 'Cloud Migration for a Financial Services Firm',
		'cat'        => 'Infrastructure',
		'client'     => 'Confidential',
		'hide'       => true,
		'date'       => '2025-08-30',
		'featured'   => false,
		'summary'    => 'Moved on-premise servers and email to Microsoft 365 and Azure with zero data loss.',
		'overview'   => 'A regulated financial services firm needed to retire ageing servers and move to the cloud securely.',
		'challenge'  => 'Ageing on-premise servers were near end of life, backups were manual and remote work was difficult.',
		'solution'   => 'We planned a phased migration to Microsoft 365 and Azure, with MFA, conditional access and automated backups, all within regulatory requirements.',
		'results'    => "Zero data loss during migration\nIT running costs down 25%\nSecure remote access for all staff",
		'tech'       => 'Microsoft 365, Azure, Intune, Veeam',
		'testimonial' => '',
	),
	array(
		'title'      => 'Inventory App for Tema Port Co.',
		'cat'        => 'Software',
		'client'     => 'Tema Port Co.',
		'date'       => '2025-06-10',
		'featured'   => false,
		'summary'    => 'A mobile barcode inventory app that replaced spreadsheets in three warehouses.',
		'overview'   => 'Tema Port Co. wanted real-time stock visibility across its warehouses.',
		'challenge'  => 'Stock was tracked in spreadsheets updated at the end of each day, leading to errors and lost items.',
		'solution'   => 'We built an Android barcode-scanning app with a web dashboard and real-time sync, integrated with their accounting software.',
		'results'    => "Stock accuracy above 99%\nMonthly stock count time cut from 3 days to 4 hours\nReal-time reports for management",
		'tech'       => 'Flutter, Node.js, MySQL',
		'testimonial' => '',
	),
);

foreach ( $projects as $i => $p ) {
	$n   = $i + 1;
	$key = 'project:' . sanitize_title( $p['title'] );
	$id  = sv_seed_find( $key, 'sv_project' );
	if ( $id ) {
		WP_CLI::log( "  exists: {$p['title']}" );
		continue;
	}
	$id = sv_seed_post(
		$key,
		array(
			'post_type'    => 'sv_project',
			'post_title'   => $p['title'],
			'post_excerpt' => $p['summary'],
			'post_content' => "<!-- wp:paragraph -->\n<p>{$p['overview']}</p>\n<!-- /wp:paragraph -->",
			'post_date'    => $p['date'] . ' 09:00:00',
		),
		array(
			'sv_featured'     => $p['featured'] ? '1' : '',
			'sv_client'       => $p['client'],
			'sv_hide_client'  => ! empty( $p['hide'] ) ? '1' : '',
			'sv_completed'    => $p['date'],
			'sv_summary'      => $p['summary'],
			'sv_challenge'    => $p['challenge'],
			'sv_solution'     => $p['solution'],
			'sv_results'      => $p['results'],
			'sv_technologies' => $p['tech'],
			'sv_testimonial'  => $p['testimonial'],
		)
	);
	wp_set_object_terms( $id, array( $cats[ $p['cat'] ] ), 'sv_project_category' );

	$cover = sv_seed_image( "$seed_dir/images/project-$n.jpg", $p['title'], $id );
	if ( $cover ) {
		set_post_thumbnail( $id, $cover );
	}
	$gallery = array();
	for ( $g = 1; $g <= 3; $g++ ) {
		$gallery[] = sv_seed_image( "$seed_dir/images/project-$n-gallery-$g.jpg", "{$p['title']} — photo $g", $id );
	}
	update_post_meta( $id, 'sv_gallery', implode( ',', array_filter( $gallery ) ) );
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
