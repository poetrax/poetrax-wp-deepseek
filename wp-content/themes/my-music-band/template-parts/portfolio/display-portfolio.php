<?php
/**
 * The template for displaying portfolio items
 *
 * @package My Music Band
 */
?>

<?php
$enable = get_theme_mod( 'my_music_band_portfolio_option', 'disabled' );

if ( ! my_music_band_check_section( $enable ) ) {
	// Bail if portfolio section is disabled.
	return;
}

$type = 'jetpack-portfolio';

	$title     = get_option( 'jetpack_portfolio_title', esc_html__( 'Projects', 'my-music-band' ) );
	$sub_title = get_option( 'jetpack_portfolio_content' );


$layout                = 'layout-three';
$background            = get_theme_mod( 'my_music_band_portfolio_bg_image', trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/images/portfolio-section-bg.jpg' );

$classes[] = $layout;
$classes[] = $type;
$classes[] = 'section';
$classes[] = 'content-frame';

if ( $background ) {
	$classes[] = 'background-image';
}
	$classes[] = 'style-one';

?>

<div id="portfolio-content-section" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="wrapper">
		<?php if ( '' != $title || $sub_title ) : ?>
			<div class="section-heading-wrapper portfolio-section-headline">
				<?php if ( '' != $title ) : ?>
					<div class="section-title-wrapper">
						<h2 class="section-title"><?php echo wp_kses_post( $title ); ?></h2>
					</div><!-- .section-title-wrapper -->
				<?php endif; ?>

				<?php if ( $sub_title ) : ?>
					<div class="taxonomy-description-wrapper section-subtitle">
						<?php echo wp_kses_post( $sub_title ); ?>
					</div><!-- .taxonomy-description-wrapper -->
				<?php endif; ?>
			</div><!-- .section-heading-wrapper -->
		<?php endif; ?>

		<div class="section-content-wrapper portfolio-content-wrapper layout-three">
			<?php
				get_template_part( 'template-parts/portfolio/post-types', 'portfolio' );
			?>
		</div><!-- .section-content-wrap -->
	</div><!-- .wrapper -->
</div><!-- #portfolio-section -->
