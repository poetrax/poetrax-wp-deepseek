<?php
/**
 * The template used for displaying playlist
 *
 * @package My Music Band
 */
?>

<?php
$enable_section = get_theme_mod( 'my_music_band_playlist_visibility', 'homepage' );

if ( ! my_music_band_check_section( $enable_section ) ) {
	// Bail if playlist is not enabled
	return;
}

$type = get_theme_mod( 'my_music_band_playlist_type', 'page' );

if ( 'demo' === $type ) {
	get_template_part( 'template-parts/playlist/demo', 'playlist' );
} elseif ( 'page' === $type || 'post' === $type || 'category' === $type ) {
	get_template_part( 'template-parts/playlist/post-type', 'playlist' );
} else {
	get_template_part( 'template-parts/playlist/image', 'playlist' );
}
