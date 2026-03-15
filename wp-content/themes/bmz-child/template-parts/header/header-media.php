<?php
/**
 * Display Header Media
 *
 * @package My Music Band
 */
?>

<?php
	$header_image = my_music_band_featured_overall_image();

	if ( 'disable' === $header_image ) {
		// Bail if all header media are disabled.
		return;
	}
?>
<div class="custom-header header-media">
	<div class="wrapper">
		<?php if ( ( is_header_video_active() && has_header_video() ) || 'disable' !== $header_image ) : ?> 
		<div class="custom-header-media">
			<?php
			if ( is_header_video_active() && has_header_video() ) {
				the_custom_header_markup();
			} elseif ( $header_image ) {
				echo '<img src="' . esc_url( $header_image ) . '"/>';
			}
			?>
		</div>
		<?php endif; ?>

		<?php my_music_band_header_media_text(); ?>
	</div><!-- .wrapper -->
</div><!-- .custom-header -->
