<?php
/**
 * Projects listing with category filters and "Load more" (FR-P2 – P4).
 * All published projects are rendered; JS filters them and reveals them in pages,
 * so filtering never reloads the page. Without JS every project is visible.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$per_page = max( 3, (int) ( $attributes['perPage'] ?? 9 ) );
$projects = get_posts(
	array(
		'post_type'      => 'sv_project',
		'posts_per_page' => 200,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	)
);
$terms    = get_terms(
	array(
		'taxonomy'   => 'sv_project_category',
		'hide_empty' => true,
	)
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sv-projects' ) ); ?> data-per-page="<?php echo esc_attr( (string) $per_page ); ?>">
	<?php if ( ! $projects ) : ?>
		<p><?php esc_html_e( 'Projects are coming soon.', 'sammav-core' ); ?></p>
	<?php else : ?>
		<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
			<div class="sv-filters" role="group" aria-label="<?php esc_attr_e( 'Filter projects by category', 'sammav-core' ); ?>">
				<button type="button" class="sv-filter is-active" data-filter="*" aria-pressed="true"><?php esc_html_e( 'All', 'sammav-core' ); ?></button>
				<?php foreach ( $terms as $term ) : ?>
					<button type="button" class="sv-filter" data-filter="<?php echo esc_attr( $term->slug ); ?>" aria-pressed="false"><?php echo esc_html( $term->name ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="sv-grid sv-grid--projects" aria-live="polite">
			<?php
			foreach ( $projects as $project ) {
				echo sammav_project_card( $project, 'h2' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
		<p class="sv-projects__empty" hidden><?php esc_html_e( 'No projects in this category yet.', 'sammav-core' ); ?></p>
		<div class="sv-projects__more">
			<button type="button" class="sv-button sv-button--outline sv-load-more" hidden><?php esc_html_e( 'Load more projects', 'sammav-core' ); ?></button>
		</div>
	<?php endif; ?>
</div>
