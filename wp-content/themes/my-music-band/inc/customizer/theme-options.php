<?php
/**
 * Theme Options
 *
 * @package My Music Band
 */
if ( ! function_exists( 'my_music_band_theme_options' ) ) :
/**
 * Add theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function my_music_band_theme_options( $wp_customize ) {
	$wp_customize->add_panel( 'my_music_band_theme_options', array(
		'title'    => esc_html__( 'Theme Options', 'my-music-band' ),
		'priority' => 130,
	) );

	// Breadcrumb Option.
	$wp_customize->add_section( 'my_music_band_breadcrumb_options', array(
			'description' => esc_html__( 'Breadcrumbs are a great way of letting your visitors find out where they are on your site with just a glance.', 'my-music-band' ),
			'panel'       => 'my_music_band_theme_options',
			'title'       => esc_html__( 'Breadcrumb', 'my-music-band' ),
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_breadcrumb_option',
			'default'           => 1,
			'sanitize_callback' => 'my_music_band_sanitize_checkbox',
			'label'             => esc_html__( 'Check to enable Breadcrumb', 'my-music-band' ),
			'section'           => 'my_music_band_breadcrumb_options',
			'type'              => 'checkbox',
		)
	);

	// Layout Options
	$wp_customize->add_section( 'my_music_band_layout_options', array(
		'title' => esc_html__( 'Layout Options', 'my-music-band' ),
		'panel' => 'my_music_band_theme_options',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_disable_fit_vid',
			'default'           => 0,
			'sanitize_callback' => 'my_music_band_sanitize_checkbox',
			'label'             => esc_html__( 'Check to Disable Fitvids Js', 'my-music-band' ),
			'section'           => 'my_music_band_layout_options',
			'type'              => 'checkbox',
		)
	);

	/* Default Layout */
	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_default_layout',
			'default'           => 'no-sidebar',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'label'             => esc_html__( 'Default Layout', 'my-music-band' ),
			'section'           => 'my_music_band_layout_options',
			'type'              => 'radio',
			'choices'           => array(
				'right-sidebar'         => esc_html__( 'Right Sidebar ( Content, Primary Sidebar )', 'my-music-band' ),
				'no-sidebar'            => esc_html__( 'No Sidebar', 'my-music-band' ),
			),
		)
	);

	/* Homepage/Archive Layout */
	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_homepage_archive_layout',
			'default'           => 'right-sidebar',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'label'             => esc_html__( 'Homepage/Archive Layout', 'my-music-band' ),
			'section'           => 'my_music_band_layout_options',
			'type'              => 'radio',
			'choices'           => array(
				'right-sidebar'         => esc_html__( 'Right Sidebar ( Content, Primary Sidebar )', 'my-music-band' ),
				'no-sidebar'            => esc_html__( 'No Sidebar', 'my-music-band' ),
			),
		)
	);

	// Excerpt Options.
	$wp_customize->add_section( 'my_music_band_excerpt_options', array(
		'panel'     => 'my_music_band_theme_options',
		'title'     => esc_html__( 'Excerpt Options', 'my-music-band' ),
	) );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_excerpt_length',
			'default'           => '20',
			'sanitize_callback' => 'absint',
			'input_attrs' => array(
				'min'   => 10,
				'max'   => 200,
				'step'  => 5,
				'style' => 'width: 60px;',
			),
			'label'    => esc_html__( 'Excerpt Length (words)', 'my-music-band' ),
			'section'  => 'my_music_band_excerpt_options',
			'type'     => 'number',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_excerpt_more_text',
			'default'           => esc_html__( 'Continue reading...', 'my-music-band' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => esc_html__( 'Read More Text', 'my-music-band' ),
			'section'           => 'my_music_band_excerpt_options',
			'type'              => 'text',
		)
	);

	// Excerpt Options.
	$wp_customize->add_section( 'my_music_band_search_options', array(
		'panel'     => 'my_music_band_theme_options',
		'title'     => esc_html__( 'Search Options', 'my-music-band' ),
	) );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_search_text',
			'default'           => esc_html__( 'Search', 'my-music-band' ),
			'sanitize_callback' => 'sanitize_text_field',
			'label'             => esc_html__( 'Search Text', 'my-music-band' ),
			'section'           => 'my_music_band_search_options',
			'type'              => 'text',
		)
	);

	// Homepage / Frontpage Options.
	$wp_customize->add_section( 'my_music_band_homepage_options', array(
		'description' => esc_html__( 'Only posts that belong to the categories selected here will be displayed on the front page', 'my-music-band' ),
		'panel'       => 'my_music_band_theme_options',
		'title'       => esc_html__( 'Homepage / Frontpage Options', 'my-music-band' ),
	) );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_recent_posts_heading',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => esc_html__( 'News', 'my-music-band' ),
			'label'             => esc_html__( 'Recent Posts Heading', 'my-music-band' ),
			'section'           => 'my_music_band_homepage_options',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_front_page_category',
			'sanitize_callback' => 'my_music_band_sanitize_category_list',
			'custom_control'    => 'My_Music_Band_Multi_Categories_Control',
			'label'             => esc_html__( 'Categories', 'my-music-band' ),
			'section'           => 'my_music_band_homepage_options',
			'type'              => 'dropdown-categories',
		)
	);

	// Pagination Options.
	$pagination_type = get_theme_mod( 'my_music_band_pagination_type', 'default' );

	$nav_desc = '';

	/**
	* Check if navigation type is Jetpack Infinite Scroll and if it is enabled
	*/
	$nav_desc = sprintf(
		wp_kses(
			__( 'For infinite scrolling, use %1$sCatch Infinite Scroll Plugin%2$s with Infinite Scroll module Enabled.', 'my-music-band' ),
			array(
				'a' => array(
					'href' => array(),
					'target' => array(),
				),
				'br'=> array()
			)
		),
		'<a target="_blank" href="https://wordpress.org/plugins/catch-infinite-scroll/">',
		'</a>'
	);

	$wp_customize->add_section( 'my_music_band_pagination_options', array(
		'description'     => $nav_desc,
		'panel'           => 'my_music_band_theme_options',
		'title'           => esc_html__( 'Pagination Options', 'my-music-band' ),
		'active_callback' => 'my_music_band_scroll_plugins_inactive'
	) );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_pagination_type',
			'default'           => 'default',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'choices'           => my_music_band_get_pagination_types(),
			'label'             => esc_html__( 'Pagination type', 'my-music-band' ),
			'section'           => 'my_music_band_pagination_options',
			'type'              => 'select',
		)
	);

	/* Scrollup Options */
	$wp_customize->add_section( 'my_music_band_scrollup', array(
		'panel'    => 'my_music_band_theme_options',
		'title'    => esc_html__( 'Scrollup Options', 'my-music-band' ),
	) );

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_disable_scrollup',
			'sanitize_callback' => 'my_music_band_sanitize_checkbox',
			'label'             => esc_html__( 'Disable Scroll Up', 'my-music-band' ),
			'section'           => 'my_music_band_scrollup',
			'type'              => 'checkbox',
		)
	);
}
endif;
add_action( 'customize_register', 'my_music_band_theme_options' );

/** Active Callback Functions */

if( ! function_exists( 'my_music_band_scroll_plugins_inactive' ) ) :
	/**
	* Return true if infinite scroll functionality exists
	*
	* @since My Music Band 0.1

	*/
	function my_music_band_scroll_plugins_inactive( $control ) {
		if ( ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'infinite-scroll' ) ) || class_exists( 'Catch_Infinite_Scroll' ) ) {
			// Support infinite scroll plugins.
			return false;
		}

		return true;
	}
endif;

