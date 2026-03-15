<?php

/**
 * Function to register control and setting
 */
if ( ! function_exists('my_music_band_register_option') ):
function my_music_band_register_option( $wp_customize, $option ) {

	// Initialize Setting.
	$wp_customize->add_setting( $option['name'], array(
		'sanitize_callback'    => $option['sanitize_callback'],
		'default'              => isset( $option['default'] ) ? $option['default'] : '',
		'transport'            => isset( $option['transport'] ) ? $option['transport'] : 'refresh',
		'theme_supports'       => isset( $option['theme_supports'] ) ? $option['theme_supports'] : '',
	) );

	$control = array(
		'label'    => $option['label'],
		'section'  => $option['section'],
		'settings' => $option['name'],
	);

	if ( isset( $option['active_callback'] ) ) {
		$control['active_callback'] = $option['active_callback'];
	}

	if ( isset( $option['priority'] ) ) {
		$control['priority'] = $option['priority'];
	}

	if ( isset( $option['choices'] ) ) {
		$control['choices'] = $option['choices'];
	}

	if ( isset( $option['type'] ) ) {
		$control['type'] = $option['type'];
	}

	if ( isset( $option['input_attrs'] ) ) {
		$control['input_attrs'] = $option['input_attrs'];
	}

	if ( isset( $option['description'] ) ) {
		$control['description'] = $option['description'];
	}

	if ( isset( $option['custom_control'] ) ) {
		$wp_customize->add_control( new $option['custom_control']( $wp_customize, $option['name'], $control ) );
	} else {
		$wp_customize->add_control( $option['name'], $control );
	}
}
endif;
/**
 * Function to reset date with respect to condition
 */
if ( ! function_exists('my_music_band_reset_data') ):
function my_music_band_reset_data() {
	if ( get_theme_mod( 'my_music_band_reset_all_settings' ) ) {
		remove_theme_mods();

		return;
	}
}
endif;
add_action( 'customize_save_after', 'my_music_band_reset_data' );

/**
 * Alphabetically sort theme options sections
 *
 * @param  wp_customize object $wp_customize wp_customize object.
 */
if ( ! function_exists('my_music_band_sort_sections_list') ):
function my_music_band_sort_sections_list( $wp_customize ) {
	foreach ( $wp_customize->sections() as $section_key => $section_object ) {
		if ( false !== strpos( $section_key, 'my_music_band_' ) && 'my_music_band_reset_all' !== $section_key && 'my_music_band_important_links' !== $section_key ) {
			$options[] = $section_key;
		}
	}

	sort( $options );

	$priority = 1;
	foreach ( $options as  $option ) {
		$wp_customize->get_section( $option )->priority = $priority++;
	}
}
endif;
add_action( 'customize_register', 'my_music_band_sort_sections_list', 99 );

/**
 * Returns an array of visibility options for featured sections
 *
 * @since My Music Band 0.1

 */
if ( ! function_exists('my_music_band_section_visibility_options') ):
function my_music_band_section_visibility_options() {
	$options = array(
		'homepage'    => esc_html__( 'Homepage / Frontpage', 'my-music-band' ),
		'entire-site' => esc_html__( 'Entire Site', 'my-music-band' ),
		'disabled'    => esc_html__( 'Disabled', 'my-music-band' ),
	);

	return apply_filters( 'my_music_band_section_visibility_options', $options );
}
endif;

/**
 * Returns an array of color schemes registered for catchresponsive.
 *
 * @since My Music Band 0.1

 */
if ( ! function_exists('my_music_band_get_pagination_types') ):
function my_music_band_get_pagination_types() {
	$pagination_types = array(
		'default' => esc_html__( 'Default(Older Posts/Newer Posts)', 'my-music-band' ),
		'numeric' => esc_html__( 'Numeric', 'my-music-band' ),
	);

	return apply_filters( 'my_music_band_get_pagination_types', $pagination_types );
}
endif;

/**
 * Generate a list of all available post array
 *
 * @param  string $post_type post type.
 * @return post_array
 */
if ( ! function_exists('my_music_band_generate_post_array') ):
function my_music_band_generate_post_array( $post_type = 'post' ) {
	$output = array();
	$posts = get_posts( array(
		'post_type'        => $post_type,
		'post_status'      => 'publish',
		'suppress_filters' => false,
		'posts_per_page'   => -1,
		)
	);

	$output['0']= esc_html__( '-- Select --', 'my-music-band' );

	foreach ( $posts as $post ) {
		$output[ $post->ID ] = ! empty( $post->post_title ) ? $post->post_title : sprintf( __( '#%d (no title)', 'my-music-band' ), $post->ID );
	}

	return $output;
}
endif;
