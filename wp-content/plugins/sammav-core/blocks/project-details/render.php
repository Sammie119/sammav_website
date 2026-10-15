<?php
/**
 * Project detail sections for the single project template (FR-P5, P6, P8).
 *
 * @var array    $attributes
 * @var WP_Block $block
 */

defined( 'ABSPATH' ) || exit;

$post_id = (int) ( $block->context['postId'] ?? get_the_ID() );
if ( ! $post_id || 'sv_project' !== get_post_type( $post_id ) ) {
	return;
}

$meta = static fn( string $key ): string => (string) get_post_meta( $post_id, $key, true );

$client       = $meta( 'sv_hide_client' ) ? __( 'Confidential client', 'sammav-core' ) : $meta( 'sv_client' );
$completed    = $meta( 'sv_completed' );
$technologies = array_filter( array_map( 'trim', explode( ',', $meta( 'sv_technologies' ) ) ) );
$results      = array_filter( array_map( 'trim', explode( "\n", $meta( 'sv_results' ) ) ) );
$gallery      = array_filter( array_map( 'absint', explode( ',', $meta( 'sv_gallery' ) ) ) );
$terms        = get_the_terms( $post_id, 'sv_project_category' );
$term         = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;

$related = $term ? get_posts(
	array(
		'post_type'      => 'sv_project',
		'posts_per_page' => 3,
		'post__not_in'   => array( $post_id ),
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'sv_project_category',
				'terms'    => $term->term_id,
			),
		),
	)
) : array();

$cta_url = add_query_arg( 'service', rawurlencode( $term ? $term->name : get_the_title( $post_id ) ), home_url( '/contact-us/' ) );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sv-project' ) ); ?>>
	<div class="sv-project__layout">
		<div class="sv-project__main">
			<?php if ( $meta( 'sv_challenge' ) ) : ?>
				<section class="sv-project__section">
					<h2><?php esc_html_e( 'The challenge', 'sammav-core' ); ?></h2>
					<?php echo wpautop( esc_html( $meta( 'sv_challenge' ) ) ); // phpcs:ignore ?>
				</section>
			<?php endif; ?>
			<?php if ( $meta( 'sv_solution' ) ) : ?>
				<section class="sv-project__section">
					<h2><?php esc_html_e( 'Our solution', 'sammav-core' ); ?></h2>
					<?php echo wpautop( esc_html( $meta( 'sv_solution' ) ) ); // phpcs:ignore ?>
				</section>
			<?php endif; ?>
			<?php if ( $results ) : ?>
				<section class="sv-project__section">
					<h2><?php esc_html_e( 'Results', 'sammav-core' ); ?></h2>
					<ul class="sv-checklist">
						<?php foreach ( $results as $result ) : ?>
							<li><?php echo esc_html( $result ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</div>

		<aside class="sv-project__facts" aria-label="<?php esc_attr_e( 'Project facts', 'sammav-core' ); ?>">
			<dl>
				<?php if ( $client ) : ?>
					<dt><?php esc_html_e( 'Client', 'sammav-core' ); ?></dt>
					<dd><?php echo esc_html( $client ); ?></dd>
				<?php endif; ?>
				<?php if ( $term ) : ?>
					<dt><?php esc_html_e( 'Category', 'sammav-core' ); ?></dt>
					<dd><?php echo esc_html( $term->name ); ?></dd>
				<?php endif; ?>
				<?php if ( $completed ) : ?>
					<dt><?php esc_html_e( 'Completed', 'sammav-core' ); ?></dt>
					<dd><time datetime="<?php echo esc_attr( $completed ); ?>"><?php echo esc_html( date_i18n( 'F Y', strtotime( $completed ) ) ); ?></time></dd>
				<?php endif; ?>
				<?php if ( $technologies ) : ?>
					<dt><?php esc_html_e( 'Technologies', 'sammav-core' ); ?></dt>
					<dd class="sv-chips">
						<?php foreach ( $technologies as $tech ) : ?>
							<span class="sv-chip"><?php echo esc_html( $tech ); ?></span>
						<?php endforeach; ?>
					</dd>
				<?php endif; ?>
			</dl>
			<?php if ( $meta( 'sv_live_url' ) ) : ?>
				<a class="sv-button sv-button--outline" href="<?php echo esc_url( $meta( 'sv_live_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit live site', 'sammav-core' ); ?></a>
			<?php endif; ?>
			<a class="sv-button" href="<?php echo esc_url( $cta_url ); ?>"><?php esc_html_e( 'Start a similar project', 'sammav-core' ); ?></a>
		</aside>
	</div>

	<?php if ( $gallery ) : ?>
		<section class="sv-project__section sv-gallery-section">
			<h2><?php esc_html_e( 'Gallery', 'sammav-core' ); ?></h2>
			<ul class="sv-gallery">
				<?php foreach ( $gallery as $image_id ) : ?>
					<?php
					$full = wp_get_attachment_image_url( $image_id, 'large' );
					$alt  = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
					if ( ! $full ) {
						continue;
					}
					?>
					<li>
						<a href="<?php echo esc_url( $full ); ?>" class="sv-lightbox-link" data-caption="<?php echo esc_attr( $alt ); ?>">
							<?php echo wp_get_attachment_image( $image_id, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $meta( 'sv_testimonial' ) ) : ?>
		<figure class="sv-quote">
			<blockquote><p><?php echo esc_html( $meta( 'sv_testimonial' ) ); ?></p></blockquote>
			<?php if ( $client && ! $meta( 'sv_hide_client' ) ) : ?>
				<figcaption><?php echo esc_html( $client ); ?></figcaption>
			<?php endif; ?>
		</figure>
	<?php endif; ?>

	<?php if ( $related ) : ?>
		<section class="sv-project__section sv-related">
			<h2><?php esc_html_e( 'Related projects', 'sammav-core' ); ?></h2>
			<div class="sv-grid sv-grid--projects">
				<?php
				foreach ( $related as $project ) {
					echo sammav_project_card( $project ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		</section>
	<?php endif; ?>
</div>
