<?php
/**
 * My Music Band functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package My Music Band
 */
if ( ! function_exists( 'my_music_band_mejs_add_container_class' ) ):
/**
 * Add an HTML class to MediaElement.js container elements to aid styling.
 *
 * Extends the core _wpmejsSettings object to add a new feature via the
 * MediaElement.js plugin API.
*/
function my_music_band_mejs_add_container_class() {
	if ( ! wp_script_is( 'mediaelement', 'done' ) ) {
		return;
	}
	?>
	<script>
	(function() {
		var settings = window._wpmejsSettings || {};

		settings.features = settings.features || mejs.MepDefaults.features;

		settings.features.push( 'my_music_band_class' );

		MediaElementPlayer.prototype.buildmy_music_band_class = function(player, controls, layers, media) {
			player.container.addClass( 'my-music-band-mejs-container' );
		}
	})();
	</script>
	<?php
}
endif;
add_action( 'wp_print_footer_scripts', 'my_music_band_mejs_add_container_class', 11 );

if ( ! function_exists( 'my_music_band_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function my_music_band_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on My Music Band Pro, use a find and replace
		 * to change 'my-music-band' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'my-music-band', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * This theme styles the visual editor to resemble the theme style,
		 * specifically font, colors, and column width.
		 *
		 * Google fonts url addition
		 *
		 * Font Awesome addition
		 */
		add_editor_style( array(
			'assets/css/editor-style.css',
			my_music_band_fonts_url(),
			trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/css/font-awesome/css/all.min.css' )
		);

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// Used in portfolio and Playlist
		set_post_thumbnail_size( 640, 640, true ); // Ratio 4:3

		// Used in Archive: Excerpt image top and Full content image top
		add_image_size( 'my-music-band-archive-top', 990, 556, true ); // Ratio 16:9

		// Used in hero content
		add_image_size( 'my-music-band-hero', 960, 720, true ); // Ratio 4:3

		// Used in featured content
		add_image_size( 'my-music-band-featured', 640, 480, true ); // Ratio 4:3

		// Used in featured slider
		add_image_size( 'my-music-band-slider', 1920, 1080, true );

		// Used in testimonials
		add_image_size( 'my-music-band-testimonial', 180, 180, true ); // Ratio 1:1


		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'menu-1'              => esc_html__( 'Primary', 'my-music-band' ),
			'social-footer'       => esc_html__( 'Footer Social Menu', 'my-music-band' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );

		// Add support for Block Styles.
		add_theme_support( 'wp-block-styles' );

		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		// Add support for full and wide align images.
		add_theme_support( 'align-wide' );

		// Add support for responsive embeds.
		add_theme_support( 'responsive-embeds' );

		// Add custom editor font sizes.
		add_theme_support(
			'editor-font-sizes',
			array(
				array(
					'name'      => __( 'Small', 'my-music-band' ),
					'shortName' => __( 'S', 'my-music-band' ),
					'size'      => 14,
					'slug'      => 'small',
				),
				array(
					'name'      => __( 'Normal', 'my-music-band' ),
					'shortName' => __( 'M', 'my-music-band' ),
					'size'      => 18,
					'slug'      => 'normal',
				),
				array(
					'name'      => __( 'Large', 'my-music-band' ),
					'shortName' => __( 'L', 'my-music-band' ),
					'size'      => 48,
					'slug'      => 'large',
				),
				array(
					'name'      => __( 'Huge', 'my-music-band' ),
					'shortName' => __( 'XL', 'my-music-band' ),
					'size'      => 72,
					'slug'      => 'huge',
				),
			)
		);

		// Add support for custom color scheme.
		add_theme_support( 'editor-color-palette', array(
			array(
				'name'  => __( 'White', 'my-music-band' ),
				'slug'  => 'white',
				'color' => '#ffffff',
			),
			array(
				'name'  => __( 'Black', 'my-music-band' ),
				'slug'  => 'black',
				'color' => '#000000',
			),
			array(
				'name'  => __( 'Medium Black', 'my-music-band' ),
				'slug'  => 'medium-black',
				'color' => '#676767',
			),
			array(
				'name'  => __( 'Gray', 'my-music-band' ),
				'slug'  => 'gray',
				'color' => '#f4f4f4',
			),
			array(
				'name'  => __( 'gold', 'my-music-band' ),
				'slug'  => 'gold',
				'color' => '#c0b283',
			),
			array(
				'name'  => __( 'Red', 'my-music-band' ),
				'slug'  => 'red',
				'color' => '#ff6465',
			),
			array(
				'name'  => __( 'Turquoise', 'my-music-band' ),
				'slug'  => 'turquoise',
				'color' => '#15aebf',
			),
			array(
				'name'  => __( 'Blue', 'my-music-band' ),
				'slug'  => 'blue',
				'color' => '#3498dc',
			),
		) );
	}
endif;
add_action( 'after_setup_theme', 'my_music_band_setup' );

/**
 * Count the number of footer sidebars to enable dynamic classes for the footer
 *
 */
if ( ! function_exists( 'my_music_band_footer_sidebar_class' ) ):
function my_music_band_footer_sidebar_class() {
	$count = 0;

	if ( is_active_sidebar( 'sidebar-2' ) ) {
		$count++;
	}

	if ( is_active_sidebar( 'sidebar-3' ) ) {
		$count++;
	}

	if ( is_active_sidebar( 'sidebar-4' ) ) {
		$count++;
	}

	$class = '';

	switch ( $count ) {
		case '1':
			$class = 'one';
			break;
		case '2':
			$class = 'two';
			break;
		case '3':
			$class = 'three';
			break;
	}

	if ( $class ) {
		echo 'class="widget-area footer-widget-area ' . esc_attr( $class ) . '"';
	}
}
endif;
/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function my_music_band_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'my_music_band_content_width', 990 );
}
add_action( 'after_setup_theme', 'my_music_band_content_width', 0 );

if ( ! function_exists( 'my_music_band_template_redirect' ) ) :
	/**
	 * Set the content width in pixels, based on the theme's design and stylesheet for different value other than the default one
	 *
	 * @global int $content_width
	 */
	function my_music_band_template_redirect() {
		$layout = my_music_band_get_theme_layout();

		if ( 'no-sidebar-full-width' === $layout ) {
			$GLOBALS['content_width'] = 1510;
		}
	}
endif;
add_action( 'template_redirect', 'my_music_band_template_redirect' );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
if ( ! function_exists( 'my_music_band_widgets_init' ) ):
function my_music_band_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'my-music-band' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here.', 'my-music-band' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 1', 'my-music-band' ),
		'id'            => 'sidebar-2',
		'description'   => esc_html__( 'Add widgets here to appear in your footer.', 'my-music-band' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 2', 'my-music-band' ),
		'id'            => 'sidebar-3',
		'description'   => esc_html__( 'Add widgets here to appear in your footer.', 'my-music-band' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer 3', 'my-music-band' ),
		'id'            => 'sidebar-4',
		'description'   => esc_html__( 'Add widgets here to appear in your footer.', 'my-music-band' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Instagram', 'my-music-band' ),
		'id'            => 'sidebar-instagram',
		'description'   => esc_html__( 'Appears above footer. This sidebar is only for Instagram Feed Gallery
', 'my-music-band' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );

}
endif;
add_action( 'widgets_init', 'my_music_band_widgets_init' );

if ( ! function_exists( 'my_music_band_fonts_url' ) ) :
	/**
	 * Register Google fonts for My Music Band
	 *
	 * Create your own my_music_band_fonts_url() function to override in a child theme.
	 *
	 * @since My Music Band 0.1
	 *
	 * @return string Google fonts URL for the theme.
	 */
	function my_music_band_fonts_url() {
		$fonts_url = '';

		/* Translators: If there are characters in your language that are not
		* supported by Montserrat, translate this to 'off'. Do not translate
		* into your own language.
		*/
		$muli = _x( 'on', 'Muli: on or off', 'my-music-band' );

		/* Translators: If there are characters in your language that are not
		* supported by Playfair Display, translate this to 'off'. Do not translate
		* into your own language.
		*/
		$playfair_display = _x( 'on', 'Playfair Display: on or off', 'my-music-band' );

		if ( 'off' !== $muli || 'off' !== $playfair_display ) {
			$font_families = array();

			if ( 'off' !== $muli ) {
			$font_families[] = 'Muli:400,600,700,800,900,400italic,700italic,800italic,900italic';
			}

			if ( 'off' !== $playfair_display ) {
			$font_families[] = 'Playfair Display:400,600,700,800,900,400italic,700italic,800italic,900italic';
			}

			$query_args = array(
				'family' => urlencode( implode( '|', $font_families ) ),
				'subset' => urlencode( 'latin,latin-ext' ),
			);

			$fonts_url = add_query_arg( $query_args, 'https://fonts.googleapis.com/css' );
		}

		require_once get_theme_file_path( 'inc/wptt-webfont-loader.php' );

		$fonts_url = wptt_get_webfont_url( $fonts_url );

		return esc_url_raw( $fonts_url );
	}
endif;

/**
 * Handles JavaScript detection.
 *
 * Adds a `js` class to the root `<html>` element when JavaScript is detected.
 *
 * @since My Music Band 0.1

 */
function my_music_band_javascript_detection() {
	echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}
add_action( 'wp_head', 'my_music_band_javascript_detection', 0 );

if ( ! function_exists( 'my_music_band_scripts' ) ) :
	/**
	 * Enqueue scripts and styles.
	 */
	function my_music_band_scripts() {
		// Add custom fonts, used in the main stylesheet.
		wp_enqueue_style( 'my-music-band-fonts', my_music_band_fonts_url(), array(), null );

		// Theme stylesheet.
		wp_enqueue_style( 'my-music-band-style', get_stylesheet_uri(), null, date( 'Ymd-Gis', filemtime( get_template_directory() . '/style.css' ) ) );

		// Theme block stylesheet.
		wp_enqueue_style( 'my-music-band-block-style', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/css/blocks.css', array( 'my-music-band-style' ), '1.0' );

		// Font Awesome.
		wp_enqueue_style( 'font-awesome', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/css/font-awesome/css/all.min.css', array(), '6.7.2', 'all' );

		wp_enqueue_script( 'my-music-band-skip-link-focus-fix', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/js/skip-link-focus-fix.min.js', array(), '201800703', true );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		wp_register_script( 'jquery-match-height', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/js/jquery.matchHeight.min.js', array( 'jquery' ), '201800703', true );

		$deps[] = 'jquery';

		$enable = get_theme_mod( 'my_music_band_featured_content_option', 'disabled' );

		if ( my_music_band_check_section( $enable ) ) {
			$deps[] = 'jquery-match-height';
		}

		wp_enqueue_script( 'my-music-band-script', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/js/functions.min.js', $deps, '201800703', true );

		wp_localize_script( 'my-music-band-script', 'musicBandScreenReaderText', array(
			'expand'   => esc_html__( 'expand child menu', 'my-music-band' ),
			'collapse' => esc_html__( 'collapse child menu', 'my-music-band' ),
		) );

		//Slider Scripts
		$enable_slider       = my_music_band_check_section( get_theme_mod( 'my_music_band_slider_option', 'disabled' ) );
		$enable_testimonial  = get_theme_mod( 'my_music_band_testimonial_option', 'disabled' );

		$testimonial_slider = my_music_band_check_section( $enable_testimonial );

		if ( $enable_slider || $testimonial_slider ) {
			wp_enqueue_script( 'jquery-cycle2', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/js/jquery.cycle/jquery.cycle2.min.js', array( 'jquery' ), '2.1.5', true );
		}

		$disable_fitvid = get_theme_mod( 'my_music_band_disable_fit_vid', 0 );

		// Enqueue fitvid if JetPack is not installed.
		if ( ! class_exists( 'Jetpack' ) && ! $disable_fitvid ) {
			wp_enqueue_script( 'jquery-fitvids', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/js/fitvids.min.js', array( 'jquery' ), '1.1', true );
		}

		// Remove Media CSS, we have ouw own CSS for this.
		wp_deregister_style('wp-mediaelement');
	}
endif;
add_action( 'wp_enqueue_scripts', 'my_music_band_scripts' );

if ( ! function_exists( 'my_music_band_block_editor_styles' ) ) :
	/**
	 * Enqueue editor styles for Gutenberg
	 *
	 * @since My Music Band 1.0
	 */
	function my_music_band_block_editor_styles() {
		// Block styles.
		wp_enqueue_style( 'my-music-band-block-editor-style', trailingslashit( esc_url ( get_template_directory_uri() ) ) . 'assets/css/editor-blocks.css' );

		// Add custom fonts.
		wp_enqueue_style( 'my-music-band-fonts', my_music_band_fonts_url(), array(), null );
	}
endif;
add_action( 'enqueue_block_editor_assets', 'my_music_band_block_editor_styles' );

if ( ! function_exists( 'my_music_band_excerpt_length' ) ) :
	/**
	 * Sets the post excerpt length to n words.
	 *
	 * function tied to the excerpt_length filter hook.
	 * @uses filter excerpt_length
	 *
	 * @since My Music Band 0.1

	 */
	function my_music_band_excerpt_length( $length ) {
		if ( is_admin() ) {
			return $length;
		}

		// Getting data from Customizer Options
		$length	= get_theme_mod( 'my_music_band_excerpt_length', 30 );

		return absint( $length );
	}
endif; //my_music_band_excerpt_length
add_filter( 'excerpt_length', 'my_music_band_excerpt_length', 999 );

if ( ! function_exists( 'my_music_band_excerpt_more' ) ) :
	/**
	 * Replaces "[...]" (appended to automatically generated excerpts) with ... and a option from customizer
	 *
	 * @return string option from customizer prepended with an ellipsis.
	 */
	function my_music_band_excerpt_more( $more ) {
		if ( is_admin() ) {
			return $more;
		}

		$more_tag_text = get_theme_mod( 'my_music_band_excerpt_more_text',  esc_html__( 'Continue reading', 'my-music-band' ) );

		$link = sprintf( '<a href="%1$s" class="more-link"><span class="readmore">%2$s</span></a>',
			esc_url( get_permalink() ),
			/* translators: %s: Name of current post */
			wp_kses_data( $more_tag_text ). '<span class="screen-reader-text">' . get_the_title( get_the_ID() ) . '</span>'
			);

		return $link;
	}
endif;
add_filter( 'excerpt_more', 'my_music_band_excerpt_more' );

if ( ! function_exists( 'my_music_band_custom_excerpt' ) ) :
	/**
	 * Adds Continue reading link to more tag excerpts.
	 *
	 * function tied to the get_the_excerpt filter hook.
	 *
	 * @since My Music Band 0.1

	 */
	function my_music_band_custom_excerpt( $output ) {
		if ( has_excerpt() && ! is_attachment() ) {
			$more_tag_text = get_theme_mod( 'my_music_band_excerpt_more_text', esc_html__( 'Continue reading', 'my-music-band' ) );

			$link = sprintf( '<a href="%1$s" class="more-link"><span class="readmore">%2$s</span></a>',
				esc_url( get_permalink() ),
				/* translators: %s: Name of current post */
				wp_kses_data( $more_tag_text ). '<span class="screen-reader-text">' . get_the_title( get_the_ID() ) . '</span>'
			);

			$link = ' &hellip; ' . $link;

			$output .= $link;
		}

		return $output;
	}
endif; //my_music_band_custom_excerpt
add_filter( 'get_the_excerpt', 'my_music_band_custom_excerpt' );

if ( ! function_exists( 'my_music_band_more_link' ) ) :
	/**
	 * Replacing Continue reading link to the_content more.
	 *
	 * function tied to the the_content_more_link filter hook.
	 *
	 * @since My Music Band 0.1

	 */
	function my_music_band_more_link( $more_link, $more_link_text ) {
		$more_tag_text = get_theme_mod( 'my_music_band_excerpt_more_text', esc_html__( 'Continue reading', 'my-music-band' ) );

		return ' &hellip; ' . str_replace( $more_link_text, wp_kses_data( $more_tag_text ), $more_link );
	}
endif; //my_music_band_more_link
add_filter( 'the_content_more_link', 'my_music_band_more_link', 10, 2 );

/**
 * Implement the Custom Header feature
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions
 */
require get_template_directory() . '/inc/customizer/customizer.php';

/**
 * Color Scheme additions
 */
require get_template_directory() . '/inc/header-background-color.php';

/**
 * Include Breadcrumbs
 */
require get_template_directory() . '/inc/breadcrumb.php';

/**
 * Include Slider
 */
require get_template_directory() . '/inc/featured-slider.php';

/**
 * Load Jetpack compatibility file
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Load Metabox
 */
function my_music_band_metabox() {
	require( get_template_directory() . '/inc/metabox/metabox.php' );
}
add_action( 'after_setup_theme', 'my_music_band_metabox' );

/**
 * Load Social Widgets
 */
require get_template_directory() . '/inc/widget-social-icons.php';

/**
 * Load TGMPA
 */
require get_template_directory() . '/inc/class-tgm-plugin-activation.php';

/**
 * Load JSON_LD Breadcrumb file.
 */
require trailingslashit( get_template_directory() ) . 'inc/json-ld-schema.php';

/**
 * Register the required plugins for this theme.
 *
 * In this example, we register five plugins:
 * - one included with the TGMPA library
 * - two from an external source, one from an arbitrary source, one from a GitHub repository
 * - two from the .org repo, where one demonstrates the use of the `is_callable` argument
 *
 * The variables passed to the `tgmpa()` function should be:
 * - an array of plugin arrays;
 * - optionally a configuration array.
 * If you are not changing anything in the configuration array, you can remove the array and remove the
 * variable from the function call: `tgmpa( $plugins );`.
 * In that case, the TGMPA default settings will be used.
 *
 * This function is hooked into `tgmpa_register`, which is fired on the WP `init` action on priority 10.
 */
function my_music_band_register_required_plugins() {
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(
		// Catch Web Tools.
		array(
			'name' => 'Catch Web Tools', // Plugin Name, translation not required.
			'slug' => 'catch-web-tools',
		),
		// Catch IDs
		array(
			'name' => 'Catch IDs', // Plugin Name, translation not required.
			'slug' => 'catch-ids',
		),
		// To Top.
		array(
			'name' => 'To top', // Plugin Name, translation not required.
			'slug' => 'to-top',
		),
		// Catch Gallery.
		array(
			'name' => 'Catch Gallery', // Plugin Name, translation not required.
			'slug' => 'catch-gallery',
		),
	);

	if ( ! class_exists( 'Catch_Infinite_Scroll_Pro' ) ) {
		$plugins[] = array(
			'name' => 'Catch Infinite Scroll', // Plugin Name, translation not required.
			'slug' => 'catch-infinite-scroll',
		);
	}

	if ( ! class_exists( 'Essential_Content_Types_Pro' ) ) {
		$plugins[] = array(
			'name' => 'Essential Content Types', // Plugin Name, translation not required.
			'slug' => 'essential-content-types',
		);
	}

	if ( ! class_exists( 'Essential_Widgets_Pro' ) ) {
		$plugins[] = array(
			'name' => 'Essential Widgets', // Plugin Name, translation not required.
			'slug' => 'essential-widgets',
		);
	}

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id'           => 'my-music-band',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
	);

	tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'my_music_band_register_required_plugins' );
