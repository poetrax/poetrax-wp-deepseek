<?php
/**
 * Featured Content options
 *
 * @package My Music Band
 */
if ( ! function_exists('my_music_band_featured_content_options' ) ):
/**
 * Add featured content options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function my_music_band_featured_content_options( $wp_customize ) {
	// Add note to ECT Featured Content Section
    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_featured_content_jetpack_note',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'My_Music_Band_Note_Control',
            'label'             => sprintf( esc_html__( 'For all Featured Content Options for this Theme, go %1$shere%2$s', 'my-music-band' ),
                '<a href="javascript:wp.customize.section( \'my_music_band_featured_content\' ).focus();">',
                 '</a>'
            ),
           'section'            => 'ect_featured_content',
            'type'              => 'description',
            'priority'          => 1,
        )
    );

    $wp_customize->add_section( 'my_music_band_featured_content', array(
			'title' => esc_html__( 'Featured Content', 'my-music-band' ),
			'panel' => 'my_music_band_theme_options',
		)
	);

	my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_featured_content_note_1',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'My_Music_Band_Note_Control',
            'active_callback'   => 'my_music_band_is_ect_featured_content_inactive',
            'label'             => sprintf( esc_html__( 'For Featured Content, install %1$sEssential Content Types%2$s Plugin with Featured Content Type Enabled', 'my-music-band' ),
                '<a target="_blank" href="https://wordpress.org/plugins/essential-content-types/">',
                '</a>'
            ),
            'section'           => 'my_music_band_featured_content',
            'type'              => 'description',
            'priority'          => 1,
        )
    );

	// Add color scheme setting and control.
	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_featured_content_option',
			'default'           => 'disabled',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'active_callback'	=> 'my_music_band_is_ect_featured_content_active',
			'choices'           => my_music_band_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'my-music-band' ),
			'section'           => 'my_music_band_featured_content',
			'type'              => 'select',
		)
	);

    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_featured_content_cpt_note',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'My_Music_Band_Note_Control',
            'active_callback'   => 'my_music_band_is_featured_content_active',
            'label'             => sprintf( esc_html__( 'For CPT heading and sub-heading, go %1$shere%2$s', 'my-music-band' ),
                 '<a href="javascript:wp.customize.control( \'featured_content_title\' ).focus();">',
                 '</a>'
            ),
            'section'           => 'my_music_band_featured_content',
            'type'              => 'description',
        )
    );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_featured_content_number',
			'default'           => 3,
			'sanitize_callback' => 'my_music_band_sanitize_number_range',
			'active_callback'   => 'my_music_band_is_featured_content_active',
			'description'       => esc_html__( 'Save and refresh the page if No. of Featured Content is changed (Max no of Featured Content is 20)', 'my-music-band' ),
			'input_attrs'       => array(
				'style' => 'width: 100px;',
				'min'   => 0,
			),
			'label'             => esc_html__( 'No of Featured Content', 'my-music-band' ),
			'section'           => 'my_music_band_featured_content',
			'type'              => 'number',
			'transport'         => 'postMessage',
		)
	);

	$number = get_theme_mod( 'my_music_band_featured_content_number', 3 );

	//loop for featured post content
	for ( $i = 1; $i <= $number ; $i++ ) {
		my_music_band_register_option( $wp_customize, array(
				'name'              => 'my_music_band_featured_content_cpt_' . $i,
				'sanitize_callback' => 'my_music_band_sanitize_post',
				'active_callback'   => 'my_music_band_is_featured_content_active',
				'label'             => esc_html__( 'Featured Content', 'my-music-band' ) . ' ' . $i ,
				'section'           => 'my_music_band_featured_content',
				'type'              => 'select',
                'choices'           => my_music_band_generate_post_array( 'featured-content' ),
			)
		);
	} // End for().
}
endif;
add_action( 'customize_register', 'my_music_band_featured_content_options', 10 );

/** Active Callback Functions **/
if( ! function_exists( 'my_music_band_is_featured_content_active' ) ) :
	/**
	* Return true if featured content is active
	*
	* @since My Music Band 0.1

	*/
	function my_music_band_is_featured_content_active( $control ) {
		$enable = $control->manager->get_setting( 'my_music_band_featured_content_option' )->value();

		return ( my_music_band_check_section( $enable ) );
	}
endif;

if ( ! function_exists( 'my_music_band_is_ect_featured_content_active' ) ) :
    /**
    * Return true if featured_content is active
    *
    * @since Solid Construction 0.1
    */
    function my_music_band_is_ect_featured_content_active( $control ) {
        return ( class_exists( 'Essential_Content_Featured_Content' ) || class_exists( 'Essential_Content_Pro_Featured_Content' ) );
    }
endif;

if ( ! function_exists( 'my_music_band_is_ect_featured_content_inactive' ) ) :
    /**
    * Return true if featured_content is active
    *
    * @since Solid Construction 0.1
    */
    function my_music_band_is_ect_featured_content_inactive( $control ) {
        return ! ( class_exists( 'Essential_Content_Featured_Content' ) || class_exists( 'Essential_Content_Pro_Featured_Content' ) );
    }
endif;

