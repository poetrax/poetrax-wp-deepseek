<?php
/**
 * Theme Customizer
 *
 * @package My Music Band
 */
if (! function_exists( 'my_music_band_customize_register' )) :
	/**
	 * Add postMessage support for site title and description for the Theme Customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	function my_music_band_customize_register( $wp_customize ) {
		$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
		$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
		$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

		if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial( 'blogname', array(
				'selector' => '.site-title a',
				'container_inclusive' => false,
				'render_callback' => 'my_music_band_customize_partial_blogname',
			) );
			$wp_customize->selective_refresh->add_partial( 'blogdescription', array(
				'selector' => '.site-description',
				'container_inclusive' => false,
				'render_callback' => 'my_music_band_customize_partial_blogdescription',
			) );
		}

		// Reset all settings to default.
		$wp_customize->add_section( 'my_music_band_reset_all', array(
			'description'   => esc_html__( 'Caution: Reset all settings to default. Refresh the page after save to view full effects.', 'my-music-band' ),
			'title'         => esc_html__( 'Reset all settings', 'my-music-band' ),
			'priority'      => 998,
		) );

		my_music_band_register_option( $wp_customize, array(
				'name'              => 'my_music_band_reset_all_settings',
				'sanitize_callback' => 'my_music_band_sanitize_checkbox',
				'label'             => esc_html__( 'Check to reset all settings to default', 'my-music-band' ),
				'section'           => 'my_music_band_reset_all',
				'transport'         => 'postMessage',
				'type'              => 'checkbox',
			)
		);
		// Reset all settings to default end.

		// Important Links.
		$wp_customize->add_section( 'my_music_band_important_links', array(
			'priority'      => 999,
			'title'         => esc_html__( 'Important Links', 'my-music-band' ),
		) );

		// Has dummy Sanitizaition function as it contains no value to be sanitized.
		my_music_band_register_option( $wp_customize, array(
				'name'              => 'my_music_band_important_links',
				'sanitize_callback' => 'sanitize_text_field',
				'custom_control'    => 'My_Music_Band_Important_Links_Control',
				'label'             => esc_html__( 'Important Links', 'my-music-band' ),
				'section'           => 'my_music_band_important_links',
				'type'              => 'my_music_band_important_links',
			)
		);
		// Important Links End.
	}
endif;
add_action( 'customize_register', 'my_music_band_customize_register' );

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function my_music_band_customize_preview_js() {
	wp_enqueue_script( 'my-music-band-customize-preview', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/js/customize-preview.min.js', array( 'customize-preview' ), '20180103', true );
}
add_action( 'customize_preview_init', 'my_music_band_customize_preview_js' );

/**
 * Include Custom Controls
 */
require get_parent_theme_file_path( 'inc/customizer/custom-controls.php' );

/**
 * Include Header Media Options
 */
require get_parent_theme_file_path( 'inc/customizer/header-media.php' );

/**
 * Include Theme Options
 */
require get_parent_theme_file_path( 'inc/customizer/theme-options.php' );

/**
 * Include Hero Content
 */
require get_parent_theme_file_path( 'inc/customizer/hero-content.php' );

/**
 * Include Featured Slider
 */
require get_parent_theme_file_path( 'inc/customizer/featured-slider.php' );

/**
 * Include Featured Content
 */
require get_parent_theme_file_path( 'inc/customizer/featured-content.php' );

/**
 * Include Testimonial
 */
require get_parent_theme_file_path( 'inc/customizer/testimonial.php' );

/**
 * Include Portfolio
 */
require get_parent_theme_file_path( 'inc/customizer/portfolio.php' );

/**
 * Include Customizer Helper Functions
 */
require get_parent_theme_file_path( 'inc/customizer/helpers.php' );

/**
 * Include Sanitization functions
 */
require get_parent_theme_file_path( 'inc/customizer/sanitize-functions.php' );

/**
 * Include Playlist
 */
require get_parent_theme_file_path( 'inc/customizer/playlist.php' );

/**
 * Include Upgrade Button
 */
require get_parent_theme_file_path( 'inc/customizer/upgrade-button/class-customize.php' );
