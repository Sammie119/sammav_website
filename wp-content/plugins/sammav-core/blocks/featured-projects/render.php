<?php
/**
 * Featured projects on the Home page (FR-H3).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$projects = get_posts(
	array(
		'post_type'      => 'sv_project',
		'posts_per_page' => max( 1, (int) ( $attributes['count'] ?? 3 ) ),
		'meta_key'       => 'sv_featured', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

if ( ! $projects ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sv-grid sv-grid--projects' ) ); ?>>
	<?php
	foreach ( $projects as $project ) {
		echo sammav_project_card( $project ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	?>
</div>
