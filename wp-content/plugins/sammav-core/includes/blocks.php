<?php
/**
 * Dynamic, server-rendered blocks. No build step: each block folder has a
 * block.json and render.php; the editor previews them with ServerSideRender.
 */

defined( 'ABSPATH' ) || exit;

function sammav_block_names(): array {
	return array( 'services', 'featured-projects', 'projects-grid', 'project-details', 'testimonials', 'team', 'contact-form' );
}

add_action(
	'init',
	static function () {
		foreach ( sammav_block_names() as $name ) {
			register_block_type( SAMMAV_CORE_DIR . 'blocks/' . $name );
		}
	}
);

add_action(
	'enqueue_block_editor_assets',
	static function () {
		wp_enqueue_script(
			'sammav-blocks-editor',
			SAMMAV_CORE_URL . 'assets/blocks-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components' ),
			SAMMAV_CORE_VERSION,
			true
		);
		wp_add_inline_script(
			'sammav-blocks-editor',
			'window.sammavBlocks = ' . wp_json_encode( array_map( static fn( $n ) => 'sammav/' . $n, sammav_block_names() ) ) . ';',
			'before'
		);
	}
);

add_filter(
	'block_categories_all',
	static function ( array $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'sammav',
				'title' => __( 'Sammav', 'sammav-core' ),
			)
		);
		return $categories;
	}
);
