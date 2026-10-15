<?php
/**
 * Services grid (FR-H2).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$services = get_posts(
	array(
		'post_type'      => 'sv_service',
		'posts_per_page' => 8,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	)
);

if ( ! $services ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sv-grid sv-grid--services' ) ); ?>>
	<?php foreach ( $services as $service ) : ?>
		<article class="sv-card sv-service-card">
			<span class="sv-service-card__icon"><?php echo sammav_icon( (string) get_post_meta( $service->ID, 'sv_icon', true ) ); // phpcs:ignore ?></span>
			<h3 class="sv-card__title"><?php echo esc_html( get_the_title( $service ) ); ?></h3>
			<p class="sv-card__text"><?php echo esc_html( wp_strip_all_tags( $service->post_content ) ); ?></p>
			<a class="sv-link-arrow" href="<?php echo esc_url( add_query_arg( 'service', rawurlencode( get_the_title( $service ) ), home_url( '/contact-us/' ) ) ); ?>">
				<?php esc_html_e( 'Enquire', 'sammav-core' ); ?><span class="screen-reader-text"> <?php echo esc_html( sprintf( /* translators: %s service name */ __( 'about %s', 'sammav-core' ), get_the_title( $service ) ) ); ?></span>
			</a>
		</article>
	<?php endforeach; ?>
</div>
