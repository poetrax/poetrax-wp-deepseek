<?php
/**
 * Display Breadcrumb
 *
 * @package My Music Band
 */
?>

<?php

if ( ! get_theme_mod( 'my_music_band_breadcrumb_option', 1 ) ) {
	// Bail if breadcrumb is disabled.
	return;
}

if ( function_exists( 'woocommerce_breadcrumb' ) && ( is_woocommerce() || is_shop() ) ) : ?>
	<div class="breadcrumb-area">
		<div class="section-wrapper">
			<?php
				$delimiter = get_theme_mod( 'my_music_band_breadcrumb_seperator', '>' );

				if ( $delimiter ) {
					$delimiter = '<span class="sep">' . $delimiter . '</span>';
				}

				$args = array(
					'delimiter' => $delimiter,
					'before'    => '<span>',
					'after'     => '</span>',
				);

				woocommerce_breadcrumb( $args );
			?>
		</div><!-- .wrapper -->
	</div><!-- .breadcrumb-area -->
<?php else:
	my_music_band_breadcrumb();
endif;