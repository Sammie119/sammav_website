<?php
/**
 * Shared helpers.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site-wide settings with defaults (editable under Settings → Sammav).
 */
function sammav_option( string $key, string $default = '' ): string {
	$options = get_option( 'sammav_settings', array() );
	$value   = isset( $options[ $key ] ) ? trim( (string) $options[ $key ] ) : '';
	return '' !== $value ? $value : $default;
}

/**
 * Service names used by the contact form dropdown.
 */
function sammav_service_names(): array {
	$names = get_posts(
		array(
			'post_type'      => 'sv_service',
			'posts_per_page' => 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'fields'         => 'ids',
		)
	);
	$names = array_map( 'get_the_title', $names );
	$names[] = __( 'Other', 'sammav-core' );
	return array_values( array_unique( $names ) );
}

/**
 * Inline SVG icons used by service cards and contact details.
 */
function sammav_icon( string $name ): string {
	$paths = array(
		'network'  => '<circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v5M12 12l-6 5M12 12l6 5"/>',
		'code'     => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4M14 5l-4 14"/>',
		'support'  => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1"/><rect x="17" y="14" width="4" height="6" rx="1"/>',
		'cloud'    => '<path d="M7 18a4 4 0 0 1-.6-7.96A6 6 0 0 1 18 9a4.5 4.5 0 0 1-.5 9H7z"/>',
		'shield'   => '<path d="M12 3 5 6v6c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6l-7-3z"/><path d="m9 12 2 2 4-4"/>',
		'web'      => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M7 6.5h.01M10 6.5h.01"/>',
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'      => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'whatsapp' => '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1a4 4 0 0 1-2-2l1-1-1-2z"/>',
	);
	$path = $paths[ $name ] ?? $paths['code'];
	return '<svg class="sv-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

function sammav_icon_choices(): array {
	return array(
		'network' => 'Network',
		'code'    => 'Code',
		'support' => 'Support',
		'cloud'   => 'Cloud',
		'shield'  => 'Shield',
		'web'     => 'Web',
	);
}

/**
 * Renders one project card (used by the grid, featured and related lists).
 */
function sammav_project_card( WP_Post $project, string $heading_tag = 'h3' ): string {
	$terms    = get_the_terms( $project, 'sv_project_category' );
	$term     = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
	$slugs    = $terms && ! is_wp_error( $terms ) ? implode( ' ', wp_list_pluck( $terms, 'slug' ) ) : '';
	$summary  = get_post_meta( $project->ID, 'sv_summary', true ) ?: get_the_excerpt( $project );
	$link     = get_permalink( $project );
	$image    = get_the_post_thumbnail(
		$project,
		'medium_large',
		array(
			'class'   => 'sv-card__img',
			'loading' => 'lazy',
			'alt'     => '',
		)
	);

	ob_start();
	?>
	<article class="sv-card sv-project-card" data-categories="<?php echo esc_attr( $slugs ); ?>">
		<a class="sv-card__media" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true"><?php echo $image; // phpcs:ignore ?></a>
		<div class="sv-card__body">
			<?php if ( $term ) : ?>
				<span class="sv-tag"><?php echo esc_html( $term->name ); ?></span>
			<?php endif; ?>
			<<?php echo tag_escape( $heading_tag ); ?> class="sv-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( get_the_title( $project ) ); ?></a></<?php echo tag_escape( $heading_tag ); ?>>
			<p class="sv-card__text"><?php echo esc_html( wp_trim_words( $summary, 24 ) ); ?></p>
			<a class="sv-link-arrow" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View project', 'sammav-core' ); ?><span class="screen-reader-text">: <?php echo esc_html( get_the_title( $project ) ); ?></span></a>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}
