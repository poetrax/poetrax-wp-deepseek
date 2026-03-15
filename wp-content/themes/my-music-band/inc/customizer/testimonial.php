<?php
/**
 * Add Testimonial Settings in Customizer
 *
 * @package My Music Band
*/

/**
 * Add testimonial options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
if ( ! function_exists( 'my_music_band_testimonial_options' ) ):
function my_music_band_testimonial_options( $wp_customize ) {
    // Add note to Jetpack Testimonial Section
    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_jetpack_testimonial_cpt_note',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'My_Music_Band_Note_Control',
            'label'             => sprintf( esc_html__( 'For Testimonial Options for this Theme, go %1$shere%2$s', 'my-music-band' ),
                '<a href="javascript:wp.customize.section( \'my_music_band_testimonials\' ).focus();">',
                 '</a>'
            ),
           'section'            => 'jetpack_testimonials',
            'type'              => 'description',
            'priority'          => 1,
        )
    );

    $wp_customize->add_section( 'my_music_band_testimonials', array(
            'panel'    => 'my_music_band_theme_options',
            'title'    => esc_html__( 'Testimonials', 'my-music-band' ),
        )
    );

    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_testimonial_note_1',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'My_Music_Band_Note_Control',
            'active_callback'   => 'my_music_band_is_ect_testimonial_inactive',
            'label'             => sprintf( esc_html__( 'For Testimonial, install %1$sEssential Content Types%2$s Plugin with Testimonial Content Type Enabled', 'my-music-band' ),
                '<a target="_blank" href="https://wordpress.org/plugins/essential-content-types/">',
                '</a>'
            ),
            'section'           => 'my_music_band_testimonials',
            'type'              => 'description',
            'priority'          => 1,
        )
    );

    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_testimonial_option',
            'default'           => 'disabled',
            'sanitize_callback' => 'my_music_band_sanitize_select',
            'active_callback'   => 'my_music_band_is_ect_testimonial_active',
            'choices'           => my_music_band_section_visibility_options(),
            'label'             => esc_html__( 'Enable on', 'my-music-band' ),
            'section'           => 'my_music_band_testimonials',
            'type'              => 'select',
            'priority'          => 1,
        )
    );

    /* Custom Testimonial Background */
    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_testimonial_bg_image',
            'default'           => trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/images/testimonial-bg.jpg',
            'active_callback'   => 'my_music_band_is_testimonial_active',
            'sanitize_callback' => 'esc_url_raw',
            'custom_control'    => 'WP_Customize_Image_Control',
            'label'             => esc_html__( 'Background Image', 'my-music-band' ),
            'section'           => 'my_music_band_testimonials',
        )
    );

    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_testimonial_cpt_note',
            'sanitize_callback' => 'sanitize_text_field',
            'custom_control'    => 'My_Music_Band_Note_Control',
            'active_callback'   => 'my_music_band_is_testimonial_active',
            'label'             => sprintf( esc_html__( 'For CPT heading and sub-heading, go %1$shere%2$s', 'my-music-band' ),
                '<a href="javascript:wp.customize.section( \'jetpack_testimonials\' ).focus();">',
                '</a>'
            ),
            'section'           => 'my_music_band_testimonials',
            'type'              => 'description',
        )
    );

    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_testimonial_number',
            'default'           => '3',
            'sanitize_callback' => 'my_music_band_sanitize_number_range',
            'active_callback'   => 'my_music_band_is_testimonial_active',
            'label'             => esc_html__( 'Number of items to show', 'my-music-band' ),
            'section'           => 'my_music_band_testimonials',
            'type'              => 'number',
            'input_attrs'       => array(
                'style'             => 'width: 100px;',
                'min'               => 0,
            ),
        )
    );

    my_music_band_register_option( $wp_customize, array(
            'name'              => 'my_music_band_testimonial_show',
            'default'           => 'full-content',
            'sanitize_callback' => 'my_music_band_sanitize_select',
            'active_callback'   => 'my_music_band_is_testimonial_active',
            'choices'           => my_music_band_content_show(),
            'label'             => esc_html__( 'Display Content', 'my-music-band' ),
            'section'           => 'my_music_band_testimonials',
            'type'              => 'select',
        )
    );

    $number = get_theme_mod( 'my_music_band_testimonial_number', 3 );

    for ( $i = 1; $i <= $number ; $i++ ) {
        //for CPT
        my_music_band_register_option( $wp_customize, array(
                'name'              => 'my_music_band_testimonial_cpt_' . $i,
                'sanitize_callback' => 'my_music_band_sanitize_post',
                'active_callback'   => 'my_music_band_is_testimonial_active',
                'label'             => esc_html__( 'Testimonial', 'my-music-band' ) . ' ' . $i ,
                'section'           => 'my_music_band_testimonials',
                'type'              => 'select',
                'choices'           => my_music_band_generate_post_array( 'jetpack-testimonial' ),
            )
        );
    } // End for().
}
endif;
add_action( 'customize_register', 'my_music_band_testimonial_options' );

/**
 * Active Callback Functions
 */
if ( ! function_exists( 'my_music_band_is_testimonial_active' ) ) :
    /**
    * Return true if testimonial is active
    *
    * @since My Music Band 1.0
    */
    function my_music_band_is_testimonial_active( $control ) {
        $enable = $control->manager->get_setting( 'my_music_band_testimonial_option' )->value();

        return ( my_music_band_check_section( $enable ) );
    }
endif;

if ( ! function_exists( 'my_music_band_is_ect_testimonial_inactive' ) ) :
    /**
    *
    * @since My Music Band 0.1
    */
    function my_music_band_is_ect_testimonial_inactive( $control ) {
        return ! ( class_exists( 'Essential_Content_Jetpack_Testimonial' ) || class_exists( 'Essential_Content_Pro_Jetpack_Testimonial' ) );
    }
endif;

if ( ! function_exists( 'my_music_band_is_ect_testimonial_active' ) ) :
    /**
    *
    * @since My Music Band 0.1
    */
    function my_music_band_is_ect_testimonial_active( $control ) {
        return ( class_exists( 'Essential_Content_Jetpack_Testimonial' ) || class_exists( 'Essential_Content_Pro_Jetpack_Testimonial' ) );
    }
endif;