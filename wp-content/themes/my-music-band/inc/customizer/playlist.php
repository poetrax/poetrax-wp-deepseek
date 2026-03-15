<?php
/**
 * Playlist Options
 *
 * @package My Music Band
 */

/**
 * Add playlist options to theme options
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
if ( ! function_exists( 'my_music_band_playlist' ) ):
function my_music_band_playlist( $wp_customize ) {
	$wp_customize->add_section( 'my_music_band_playlist', array(
			'title' => esc_html__( 'Playlist', 'my-music-band' ),
			'panel' => 'my_music_band_theme_options',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_playlist_visibility',
			'default'           => 'disabled',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'choices'           => my_music_band_section_visibility_options(),
			'label'             => esc_html__( 'Enable on', 'my-music-band' ),
			'section'           => 'my_music_band_playlist',
			'type'              => 'select',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_playlist_section_title',
			'default'           => esc_html__( 'New Releases', 'my-music-band' ),
			'sanitize_callback' => 'wp_kses_post',
			'active_callback'   => 'my_music_band_is_playlist_active',
			'label'             => esc_html__( 'Section Title', 'my-music-band' ),
			'section'           => 'my_music_band_playlist',
			'type'              => 'text',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_playlist',
			'default'           => '0',
			'sanitize_callback' => 'my_music_band_sanitize_post',
			'active_callback'   => 'my_music_band_is_playlist_active',
			'label'             => esc_html__( 'Page', 'my-music-band' ),
			'section'           => 'my_music_band_playlist',
			'type'              => 'dropdown-pages',
		)
	);

	my_music_band_register_option( $wp_customize, array(
			'name'              => 'my_music_band_playlist_show',
			'default'           => 'full-content',
			'sanitize_callback' => 'my_music_band_sanitize_select',
			'active_callback'   => 'my_music_band_is_playlist_active',
			'choices'           => my_music_band_content_show(),
			'label'             => esc_html__( 'Display Content', 'my-music-band' ),
			'section'           => 'my_music_band_playlist',
			'type'              => 'select',
		)
	);
}
endif;
add_action( 'customize_register', 'my_music_band_playlist', 12 );

/** Active Callback Functions **/
if ( ! function_exists( 'my_music_band_is_playlist_active' ) ) :
	/**
	* Return true if playlist is active
	*
	* @since My Music Band 0.1

	*/
	function my_music_band_is_playlist_active( $control ) {
		global $wp_query;

		$page_id = $wp_query->get_queried_object_id();

		// Front page display in Reading Settings
		$page_for_posts = get_option( 'page_for_posts' );

		$enable = $control->manager->get_setting( 'my_music_band_playlist_visibility' )->value();

		//return true only if previewed page on customizer matches the type of content option selected
		return ( 'entire-site' == $enable  || ( ( is_front_page() || ( is_home() && $page_for_posts != $page_id ) ) &&	 'homepage' == $enable )
			);
	}
endif;

if ( ! function_exists( 'my_music_band_is_page_playlist_active' ) ) :
	/**
	* Return true if page playlist is active
	*
	* @since My Music Band 0.1

	*/
	function my_music_band_is_page_playlist_active( $control ) {
		$type = $control->manager->get_setting( 'my_music_band_playlist_type' )->value();

		return ( my_music_band_is_playlist_active( $control ) && 'page' == $type );
	}
endif;


if ( ! function_exists( 'my_music_band_is_post_page_category_playlist_active' ) ) :
	/**
	* Return true if post/page/category playlist is active
	*
	* @since My Music Band 0.1

	*/
	function my_music_band_is_post_page_category_playlist_active( $control ) {
		$type = $control->manager->get_setting( 'my_music_band_playlist_type' )->value();

		return ( my_music_band_is_playlist_active( $control ) && ( 'page' == $type || 'post' == $type || 'category' == $type ) );
	}
endif;
