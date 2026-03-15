<?php
/**
 * Featured Slider Options
 *
 * @package My Music Band
 */
if( ! function_exists( 'my_music_band_slider_options' ) ) : 
/**
 * Add hero content options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function my_music_band_slider_options( $wp_customize ) {
	$wp_customize->add_section( 'my_music_band_featured_slider', array(
			'panel' => 'my_music_band_theme_options',
			'title' => esc_html__( 'Featured Slider', 'my-music-band' ),
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_slider_option',
			'default'           => 'disabled',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'choices'           => my_music_band_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'my-music-band' ),
			'section'           => 'my_music_band_featured_slider',
			'type'              => 'select',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_slider_number',
			'default'           => '4',
			'sanitize_callback' => 'my_music_band_sanitize_number_range',

			'active_callback'   => 'my_music_band_is_slider_active',
			'description'       => esc_html__( 'Save and refresh the page if No. of Slides is changed (Max no of slides is 20)', 'my-music-band' ),
			'input_attrs'       => array(
				'style' => 'width: 45px;',
				'min'   => 0,
				'max'   => 20,
				'step'  => 1,
			),
			'label'             => esc_html__( 'No of Slides', 'my-music-band' ),
			'section'           => 'my_music_band_featured_slider',
			'type'              => 'number',
			'transport'         => 'postMessage',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_slider_content_show',
			'default'           => 'excerpt',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'active_callback'   => 'my_music_band_is_slider_active',
			'choices'           => my_music_band_content_show(),
			'label'             => esc_html__( 'Display Content', 'my-music-band' ),
			'section'           => 'my_music_band_featured_slider',
			'type'              => 'select',
		)
	);

	$slider_number = get_theme_mod( 'my_music_band_slider_number', 4 );

	for ( $i = 1; $i <= $slider_number ; $i++ ) {
		// Page Sliders
		my_music_band_register_option( $wp_customize, array(
				'name'              =>'my_music_band_slider_page_' . $i,
				'sanitize_callback' => 'my_music_band_sanitize_post',
				'active_callback'   => 'my_music_band_is_slider_active',
				'label'             => esc_html__( 'Page', 'my-music-band' ) . ' # ' . $i,
				'section'           => 'my_music_band_featured_slider',
				'type'              => 'dropdown-pages',
			)
		);
	} // End for().
}
endif;
add_action( 'customize_register', 'my_music_band_slider_options' );


/**
 * Returns an array of feature slider transition effects
 *
 * @since My Music Band 0.1

 */
if(!function_exists('my_music_band_slider_transition_effects')):
function my_music_band_slider_transition_effects() {
	$options = array(
		'fade'       => esc_html__( 'Fade', 'my-music-band' ),
		'fadeout'    => esc_html__( 'Fade Out', 'my-music-band' ),
		'none'       => esc_html__( 'None', 'my-music-band' ),
		'scrollHorz' => esc_html__( 'Scroll Horizontal', 'my-music-band' ),
		'scrollVert' => esc_html__( 'Scroll Vertical', 'my-music-band' ),
		'flipHorz'   => esc_html__( 'Flip Horizontal', 'my-music-band' ),
		'flipVert'   => esc_html__( 'Flip Vertical', 'my-music-band' ),
		'tileSlide'  => esc_html__( 'Tile Slide', 'my-music-band' ),
		'tileBlind'  => esc_html__( 'Tile Blind', 'my-music-band' ),
		'shuffle'    => esc_html__( 'Shuffle', 'my-music-band' ),
	);

	return apply_filters( 'my_music_band_slider_transition_effects', $options );
}
endif;
/**
 * Returns an array of featured content show registered
 *
 * @since My Music Band 0.1

 */
if(!function_exists('my_music_band_content_show')):
function my_music_band_content_show() {
	$options = array(
		'excerpt'      => esc_html__( 'Show Excerpt', 'my-music-band' ),
		'full-content' => esc_html__( 'Full Content', 'my-music-band' ),
		'hide-content' => esc_html__( 'Hide Content', 'my-music-band' ),
	);
	return apply_filters( 'my_music_band_content_show', $options );
}
endif;


/**
 * Returns an array of featured content show registered
 *
 * @since My Music Band 0.1

 */
if(!function_exists( 'my_music_band_meta_show' )):
function my_music_band_meta_show() {
	$options = array(
		'show-meta'      => esc_html__( 'Show Meta', 'my-music-band' ),
		'hide-meta' => esc_html__( 'Hide Meta', 'my-music-band' ),
	);
	return apply_filters( 'my_music_band_content_show', $options );
}
endif;

/** Active Callback Functions */

if( ! function_exists( 'my_music_band_is_slider_active' ) ) :
	/**
	* Return true if slider is active
	*
	* @since My Music Band 0.1

	*/
	function my_music_band_is_slider_active( $control ) {
		$enable = $control->manager->get_setting( 'my_music_band_slider_option' )->value();

		return ( my_music_band_check_section( $enable ) );
	}
endif;