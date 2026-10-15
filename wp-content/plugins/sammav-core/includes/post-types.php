<?php
/**
 * Custom post types and taxonomy.
 */

defined( 'ABSPATH' ) || exit;

function sammav_register_post_types(): void {
	register_post_type(
		'sv_project',
		array(
			'labels'        => array(
				'name'          => __( 'Projects', 'sammav-core' ),
				'singular_name' => __( 'Project', 'sammav-core' ),
				'add_new_item'  => __( 'Add New Project', 'sammav-core' ),
				'edit_item'     => __( 'Edit Project', 'sammav-core' ),
				'all_items'     => __( 'All Projects', 'sammav-core' ),
			),
			'public'        => true,
			'has_archive'   => 'projects',
			'rewrite'       => array( 'slug' => 'projects', 'with_front' => false ),
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 5,
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ),
			'capability_type' => 'post',
		)
	);

	register_taxonomy(
		'sv_project_category',
		'sv_project',
		array(
			'labels'            => array(
				'name'          => __( 'Project Categories', 'sammav-core' ),
				'singular_name' => __( 'Project Category', 'sammav-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'project-category', 'with_front' => false ),
		)
	);

	$private_types = array(
		'sv_service'     => array( __( 'Services', 'sammav-core' ), __( 'Service', 'sammav-core' ), 'dashicons-admin-tools', array( 'title', 'editor', 'page-attributes', 'custom-fields' ) ),
		'sv_testimonial' => array( __( 'Testimonials', 'sammav-core' ), __( 'Testimonial', 'sammav-core' ), 'dashicons-format-quote', array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ) ),
		'sv_team'        => array( __( 'Team', 'sammav-core' ), __( 'Team Member', 'sammav-core' ), 'dashicons-groups', array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ) ),
	);

	foreach ( $private_types as $type => [ $plural, $singular, $icon, $supports ] ) {
		register_post_type(
			$type,
			array(
				'labels'             => array(
					'name'          => $plural,
					'singular_name' => $singular,
					/* translators: %s: item type */
					'add_new_item'  => sprintf( __( 'Add New %s', 'sammav-core' ), $singular ),
					/* translators: %s: item type */
					'edit_item'     => sprintf( __( 'Edit %s', 'sammav-core' ), $singular ),
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_rest'       => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'menu_icon'          => $icon,
				'menu_position'      => 6,
				'supports'           => $supports,
				'capability_type'    => 'post',
			)
		);
	}

	register_post_type(
		'sv_enquiry',
		array(
			'labels'          => array(
				'name'          => __( 'Enquiries', 'sammav-core' ),
				'singular_name' => __( 'Enquiry', 'sammav-core' ),
				'edit_item'     => __( 'View Enquiry', 'sammav-core' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 4,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'sammav_register_post_types' );

/**
 * Projects archive shows the newest first, 9 per page (the grid block handles "load more").
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ) {
		if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'sv_project' ) ) {
			$query->set( 'posts_per_page', 9 );
		}
	}
);
