<?php
/**
 * The template for displaying featured content
 *
 * @package My Music Band
 */
?>

<?php
$enable_content = get_theme_mod( 'my_music_band_featured_content_option', 'disabled' );

if ( ! my_music_band_check_section( $enable_content ) ) {
	// Bail if featured content is disabled.
	return;
}

$type = 'featured-content';

	$featured_posts = my_music_band_get_featured_posts();

	if ( empty( $featured_posts ) ) {
		return;
	}

	$title     = get_option( 'featured_content_title', esc_html__( 'Contents', 'my-music-band' ) );
	$sub_title = get_option( 'featured_content_content' );

$layout = 'layout-three';

$classes[] = $layout;
$classes[] = $type;
$classes[] = 'section';
?>

<div id="featured-content-section" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="wrapper">
		<?php if ( '' !== $title || $sub_title ) : ?>
			<div class="section-heading-wrapper featured-section-headline">
				<?php if ( '' !== $title ) : ?>
					<div class="section-title-wrapper">
						<h2 class="section-title"><?php echo wp_kses_post( $title ); ?></h2>
					</div><!-- .section-title-wrapper -->
				<?php endif; ?>

				<?php if ( $sub_title ) : ?>
					<div class="taxonomy-description-wrapper section-subtitle">
						<?php echo wp_kses_post( $sub_title ); ?>
					</div><!-- .taxonomy-description-wrapper -->
				<?php endif; ?>
			</div><!-- .section-heading-wrap -->
		<?php endif; ?>

		<div class="section-content-wrapper featured-content-wrapper <?php echo esc_attr( $layout ); ?>">

			<?php
			foreach ( $featured_posts as $post ) {
				setup_postdata( $post );

				// Include the featured content template.
				get_template_part( 'template-parts/featured-content/content', 'featured' );
			}

			wp_reset_postdata(); ?>
		</div><!-- .section-content-wrap -->
	</div><!-- .wrapper -->
</div><!-- #featured-content-section -->
