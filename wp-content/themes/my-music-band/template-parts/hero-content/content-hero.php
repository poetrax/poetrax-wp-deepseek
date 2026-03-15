<?php
/**
 * The template used for displaying hero content
 *
 * @package My Music Band
 */

$enable_section = get_theme_mod( 'my_music_band_hero_content_visibility', 'disabled' );

if ( ! my_music_band_check_section( $enable_section ) ) {
	// Bail if hero content is not enabled
	return;
}

	get_template_part( 'template-parts/hero-content/post-type', 'hero' );

