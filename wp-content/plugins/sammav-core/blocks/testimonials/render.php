<?php
/**
 * Testimonials carousel (FR-H6). Scroll-snap track; buttons are added by view.js.
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$items = get_posts(
	array(
		'post_type'      => 'sv_testimonial',
		'posts_per_page' => 10,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	)
);

if ( ! $items ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sv-testimonials' ) ); ?>>
	<div class="sv-testimonials__track" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Client testimonials', 'sammav-core' ); ?>">
		<?php foreach ( $items as $item ) : ?>
			<figure class="sv-quote sv-testimonials__item">
				<blockquote><p><?php echo esc_html( wp_strip_all_tags( $item->post_content ) ); ?></p></blockquote>
				<figcaption>
					<strong><?php echo esc_html( get_post_meta( $item->ID, 'sv_person', true ) ?: get_the_title( $item ) ); ?></strong>
					<span><?php echo esc_html( (string) get_post_meta( $item->ID, 'sv_company', true ) ); ?></span>
				</figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
	<div class="sv-testimonials__nav" hidden>
		<button type="button" class="sv-testimonials__prev" aria-label="<?php esc_attr_e( 'Previous testimonial', 'sammav-core' ); ?>">&#8249;</button>
		<button type="button" class="sv-testimonials__next" aria-label="<?php esc_attr_e( 'Next testimonial', 'sammav-core' ); ?>">&#8250;</button>
	</div>
</div>
