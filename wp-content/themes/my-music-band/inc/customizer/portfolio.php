<?php
/**
 * Add Portfolio Settings in Customizer
 *
 * @package My Music Band
 */

/**
 * Add portfolio options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
if( ! function_exists( 'my_music_band_portfolio_options' ) ):
function my_music_band_portfolio_options( $wp_customize ) {
	// Add note to Jetpack Portfolio Section
	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_jetpack_portfolio_cpt_note',
			'sanitize_callback' => 'sanitize_text_field',
			'custom_control'    => 'My_Music_Band_Note_Control',
			'label'             => sprintf( esc_html__( 'For Portfolio Options for this Theme, go %1$shere%2$s', 'my-music-band' ),
				 '<a href="javascript:wp.customize.section( \'my_music_band_portfolio\' ).focus();">',
				 '</a>'
			),
			'section'           => 'jetpack_portfolio',
			'type'              => 'description',
			'priority'          => 1,
		)
	);

	$wp_customize->add_section( 'my_music_band_portfolio', array(
			'panel'    => 'my_music_band_theme_options',
			'title'    => esc_html__( 'Portfolio', 'my-music-band' ),
		)
	);

	my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_portfolio_note_1',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'My_Music_Band_Note_Control',
            'active_callback'   => 'my_music_band_is_ect_portfolio_inactive',
            'label'             => sprintf( esc_html__( 'For Portfolio, install %1$sEssential Content Types%2$s Plugin with Portfolio Content Type Enabled', 'my-music-band' ),
                '<a target="_blank" href="https://wordpress.org/plugins/essential-content-types/">',
                '</a>'
            ),
            'section'           => 'my_music_band_portfolio',
            'type'              => 'description',
            'priority'          => 1,
        )
    );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_portfolio_option',
			'default'           => 'disabled',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'active_callback'	=> 'my_music_band_is_ect_portfolio_active',
			'choices'           => my_music_band_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'my-music-band' ),
			'section'           => 'my_music_band_portfolio',
			'type'              => 'select',
		)
	);

	/* Custom Portfolio Background */
	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_portfolio_bg_image',
			'default'           => trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/images/portfolio-section-bg.jpg',
			'active_callback'   => 'my_music_band_is_portfolio_active',
			'sanitize_callback' => 'esc_url_raw',
			'custom_control'    => 'WP_Customize_Image_Control',
			'label'             => esc_html__( 'Background Image', 'my-music-band' ),
			'section'           => 'my_music_band_portfolio',
		)
	);


	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_portfolio_cpt_note',
			'sanitize_callback' => 'sanitize_text_field',
			'custom_control'    => 'My_Music_Band_Note_Control',
			'active_callback'   => 'my_music_band_is_portfolio_active',
			'label'             => sprintf( esc_html__( 'For CPT heading and sub-heading, go %1$shere%2$s', 'my-music-band' ),
				 '<a href="javascript:wp.customize.control( \'jetpack_portfolio_title\' ).focus();">',
				 '</a>'
			),
			'section'           => 'my_music_band_portfolio',
			'type'              => 'description',
		)
	);


	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_portfolio_number',
			'default'           => '3',
			'sanitize_callback' => 'my_music_band_sanitize_number_range',
			'active_callback'   => 'my_music_band_is_portfolio_active',
			'label'             => esc_html__( 'Number of items to show', 'my-music-band' ),
			'section'           => 'my_music_band_portfolio',
			'type'              => 'number',
			'input_attrs'       => array(
				'style'             => 'width: 100px;',
				'min'               => 0,
			),
		)
	);


	$number = get_theme_mod( 'my_music_band_portfolio_number', 3 );

	for ( $i = 1; $i <= $number ; $i++ ) {
		//for CPT
		my_music_band_register_option( $wp_customize, array(
				'name'              => 'my_music_band_portfolio_cpt_' . $i,
				'sanitize_callback' => 'my_music_band_sanitize_post',
				'active_callback'   => 'my_music_band_is_portfolio_active',
				'label'             => esc_html__( 'Portfolio', 'my-music-band' ) . ' ' . $i ,
				'section'           => 'my_music_band_portfolio',
				'type'              => 'select',
				'choices'           => my_music_band_generate_post_array( 'jetpack-portfolio' ),
			)
		);
	} // End for().
}
endif;
add_action( 'customize_register', 'my_music_band_portfolio_options' );

/**
 * Active Callback Functions
 */
if ( ! function_exists( 'my_music_band_is_portfolio_active' ) ) :
	/**
	* Return true if portfolio is active
	*
	* @since My Music Band 0.1
	*/
	function my_music_band_is_portfolio_active( $control ) {
		$enable = $control->manager->get_setting( 'my_music_band_portfolio_option' )->value();

		//return true only if previwed page on customizer matches the type of content option selected
		return ( my_music_band_check_section( $enable ) );
	}
endif;

if ( ! function_exists( 'my_music_band_is_ect_portfolio_inactive' ) ) :
    /**
    *
    * @since Adonis 0.1
    */
    function my_music_band_is_ect_portfolio_inactive( $control ) {
        return ! ( class_exists( 'Essential_Content_Jetpack_Portfolio' ) || class_exists( 'Essential_Content_Pro_Jetpack_Portfolio' ) );
    }
endif;

if ( ! function_exists( 'my_music_band_is_ect_portfolio_active' ) ) :
    /**
    *
    * @since Adonis 0.1
    */
    function my_music_band_is_ect_portfolio_active( $control ) {
        return ( class_exists( 'Essential_Content_Jetpack_Portfolio' ) || class_exists( 'Essential_Content_Pro_Jetpack_Portfolio' ) );
    }
endif;
