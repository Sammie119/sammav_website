<?php
/**
 * Team members (FR-A3).
 *
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$members = get_posts(
	array(
		'post_type'      => 'sv_team',
		'posts_per_page' => 24,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	)
);

if ( ! $members ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sv-grid sv-grid--team' ) ); ?>>
	<?php foreach ( $members as $member ) : ?>
		<?php $linkedin = (string) get_post_meta( $member->ID, 'sv_linkedin', true ); ?>
		<article class="sv-card sv-team-card">
			<?php
			echo get_the_post_thumbnail(
				$member,
				'medium',
				array(
					'class'   => 'sv-team-card__photo',
					'loading' => 'lazy',
					'alt'     => sprintf( /* translators: %s name */ __( 'Photo of %s', 'sammav-core' ), get_the_title( $member ) ),
				)
			);
			?>
			<h3 class="sv-card__title"><?php echo esc_html( get_the_title( $member ) ); ?></h3>
			<p class="sv-team-card__role"><?php echo esc_html( (string) get_post_meta( $member->ID, 'sv_role', true ) ); ?></p>
			<p class="sv-card__text"><?php echo esc_html( wp_strip_all_tags( $member->post_content ) ); ?></p>
			<?php if ( $linkedin ) : ?>
				<a class="sv-link-arrow" href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener">LinkedIn<span class="screen-reader-text"> <?php echo esc_html( get_the_title( $member ) ); ?></span></a>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
</div>
