<?php
/**
 * Header Media Options
 *
 * @package My Music Band
 */
if( ! function_exists( 'my_music_band_header_media_options' ) ):
/**
 * Add Header Media options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function my_music_band_header_media_options( $wp_customize ) {
	$wp_customize->get_section( 'header_image' )->description = esc_html__( 'If you add video, it will only show up on Homepage/FrontPage. Other Pages will use Header/Post/Page Image depending on your selection of option. Header Image will be used as a fallback while the video loads ', 'my-music-band' );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_header_media_option',
			'default'           => 'entire-site-page-post',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'choices'           => array(
				'homepage'               => esc_html__( 'Homepage / Frontpage', 'my-music-band' ),
				'exclude-home'           => esc_html__( 'Excluding Homepage', 'my-music-band' ),
				'exclude-home-page-post' => esc_html__( 'Excluding Homepage, Page/Post Featured Image', 'my-music-band' ),
				'entire-site'            => esc_html__( 'Entire Site', 'my-music-band' ),
				'entire-site-page-post'  => esc_html__( 'Entire Site, Page/Post Featured Image', 'my-music-band' ),
				'pages-posts'            => esc_html__( 'Pages and Posts', 'my-music-band' ),
				'disable'                => esc_html__( 'Disabled', 'my-music-band' ),
			),
			'label'             => esc_html__( 'Enable on', 'my-music-band' ),
			'section'           => 'header_image',
			'type'              => 'select',
			'priority'          => 1,
		)
	);


	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_header_media_layout',
			'default'           => 'header-media-fluid',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'choices'           => array(
				'header-media-fluid' => esc_html__( 'Fluid', 'my-music-band' ),
				'header-media-boxed' => esc_html__( 'Boxed', 'my-music-band' ),
			),
			'label'             => esc_html__( 'Header Media Layout', 'my-music-band' ),
			'section'           => 'header_image',
			'type'              => 'radio',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_header_media_title',
			'sanitize_callback' => 'wp_kses_post',
			'default'           => esc_html__( 'Header Media', 'my-music-band' ),
			'label'             => esc_html__( 'Header Media Small Title', 'my-music-band' ),
			'section'           => 'header_image',
			'type'              => 'text',
		)
	);

    my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_header_media_text',
			'sanitize_callback' => 'wp_kses_post',
			'default'           => esc_html__( 'Go to Theme Customizer', 'my-music-band' ),
			'label'             => esc_html__( 'Header Media Large Title', 'my-music-band' ),
			'section'           => 'header_image',
			'type'              => 'textarea',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_header_media_url',
			'default'           => '#',
			'sanitize_callback' => 'esc_url_raw',
			'label'             => esc_html__( 'Header Media Url', 'my-music-band' ),
			'section'           => 'header_image',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_header_media_url_text',
			'default'           => esc_html__( 'More', 'my-music-band' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => esc_html__( 'Header Media Url Text', 'my-music-band' ),
			'section'           => 'header_image',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_header_url_target',
			'sanitize_callback' => 'my_music_band_sanitize_checkbox',
			'label'             => esc_html__( 'Check to Open Link in New Window/Tab', 'my-music-band' ),
			'section'           => 'header_image',
			'type'              => 'checkbox',
		)
	);
}
endif;
add_action( 'customize_register', 'my_music_band_header_media_options' );
