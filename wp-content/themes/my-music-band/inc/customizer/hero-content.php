<?php
/**
 * Hero Content Options
 *
 * @package My Music Band
 */

/**
 * Add hero content options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
if ( ! function_exists( 'my_music_band_hero_content_options' ) ):
function my_music_band_hero_content_options( $wp_customize ) {
	$wp_customize->add_section( 'my_music_band_hero_content_options', array(
			'title' => esc_html__( 'Hero Content', 'my-music-band' ),
			'panel' => 'my_music_band_theme_options',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_hero_content_visibility',
			'default'           => 'disabled',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'choices'           => my_music_band_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'my-music-band' ),
			'section'           => 'my_music_band_hero_content_options',
			'type'              => 'select',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_hero_content',
			'default'           => '0',
			'sanitize_callback' => 'my_music_band_sanitize_post',
			'active_callback'   => 'my_music_band_is_hero_content_active',
			'label'             => esc_html__( 'Page', 'my-music-band' ),
			'section'           => 'my_music_band_hero_content_options',
			'type'              => 'dropdown-pages',
		)
	);
}
endif;
add_action( 'customize_register', 'my_music_band_hero_content_options' );

/** Active Callback Functions **/
if ( ! function_exists( 'my_music_band_is_hero_content_active' ) ) :
	/**
	* Return true if hero content is active
	*
	* @since My Music Band 0.1

	*/
	function my_music_band_is_hero_content_active( $control ) {
		$enable = $control->manager->get_setting( 'my_music_band_hero_content_visibility' )->value();

		return ( my_music_band_check_section( $enable ) );
	}
endif;
