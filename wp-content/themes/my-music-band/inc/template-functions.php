<?php
/**
 * Custom functions that act independently of the theme templates
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package My Music Band
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @since My Music Band 0.1

 *
 * @param array $classes Classes for the body element.
 * @return array (Maybe) filtered body classes.
 */
if ( ! function_exists( 'my_music_band_body_classes' ) ):
function my_music_band_body_classes( $classes ) {
	// Adds a class of custom-background-image to sites with a custom background image.
	if ( get_background_image() ) {
		$classes[] = 'custom-background-image';
	}

	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Always add a front-page class to the front page.
	if ( is_front_page() && ! is_home() ) {
		$classes[] = 'page-template-front-page';
	}
		$classes[] = 'fluid-layout';

		$classes[] = 'navigation-classic';

	// Adds a class with respect to layout selected.
	$layout  = my_music_band_get_theme_layout();
	$sidebar = my_music_band_get_sidebar_id();

	$layout_class = "no-sidebar content-width-layout";

	if ( 'no-sidebar-full-width' === $layout ) {
		$layout_class = 'no-sidebar full-width-layout';
	} elseif ( 'left-sidebar' === $layout ) {
		if ( '' !== $sidebar ) {
			$layout_class = 'two-columns-layout content-right';
		}
	} elseif ( 'right-sidebar' === $layout ) {
		if ( '' !== $sidebar ) {
			$layout_class = 'two-columns-layout content-left';
		}
	}

	$classes[] = $layout_class;

	$classes[] = 'excerpt-image-top';

	$classes[] = esc_attr( get_theme_mod( 'my_music_band_header_media_layout', 'header-media-fluid' ) );

	$enable_slider = my_music_band_check_section( get_theme_mod( 'my_music_band_slider_option', 'disabled' ) );

	$enable_breadcrumb = get_theme_mod( 'my_music_band_breadcrumb_option', 1 );

	$enable_header_media = true;
	$header_image        = my_music_band_featured_overall_image();

	if ( 'disable' === $header_image ) {
		$enable_header_media = false;
	} else { 
		$classes[] = 'has-header-media';
	}

	if ( ! my_music_band_has_header_media_text() ) {
		$classes[] = 'header-media-text-disabled';
	}

	if ( ( ! $enable_slider && ! $enable_header_media)  ||  !$enable_breadcrumb ) {
		$classes[] = 'primary-nav-bottom-border';
	}

	return $classes;
}
endif;
add_filter( 'body_class', 'my_music_band_body_classes' );

/**
 * Add a pingback url auto-discovery header for singularly identifiable articles.
 */
function my_music_band_pingback_header() {
	if ( is_singular() && pings_open() ) {
		echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
	}
}
add_action( 'wp_head', 'my_music_band_pingback_header' );

if ( ! function_exists( 'my_music_band_comments' ) ) :
	/**
	 * Enable/Disable Comments
	 *
	 * @uses comment_form_default_fields filter
	 * @since My Music Band 0.1

	 */
	function my_music_band_comments( $open, $post_id ) {
		$comment_select = get_theme_mod( 'my_music_band_comment_option', 'use-wordpress-setting' );

	    if( 'disable-completely' === $comment_select ) {
			return false;
		} elseif( 'disable-in-pages' === $comment_select && is_page() ) {
			return false;
		}

	    return $open;
	}
endif; // my_music_band_comments.
add_filter( 'comments_open', 'my_music_band_comments', 10, 2 );

if ( ! function_exists( 'my_music_band_comment_form_fields' ) ) :
	/**
	 * Modify Comment Form Fields
	 *
	 * @uses comment_form_default_fields filter
	 * @since My Music Band 0.1

	 */
	function my_music_band_comment_form_fields( $fields ) {
	    $disable_website = get_theme_mod( 'my_music_band_website_field' );

	    if ( isset( $fields['url'] ) && $disable_website ) {
			unset( $fields['url'] );
		}

		return $fields;
	}
endif; // my_music_band_comment_form_fields.
add_filter( 'comment_form_default_fields', 'my_music_band_comment_form_fields' );

/**
 * Adds portfolio background CSS
 */
if ( ! function_exists( 'my_music_band_portfolio_bg_css' ) ): 
function my_music_band_portfolio_bg_css() {
	$background = get_theme_mod( 'my_music_band_portfolio_bg_image', trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/images/portfolio-section-bg.jpg' );

	$css = ' background-image: url("' . esc_url( $background ) . '");';

	if ( '' !== $css ) {
		$css = '#portfolio-content-section.background-image { ' . $css . '}';
	}

	wp_add_inline_style( 'my-music-band-style', $css );
}
endif;
add_action( 'wp_enqueue_scripts', 'my_music_band_portfolio_bg_css', 11 );

/**
 * Adds testimonial background CSS
 */
if( ! function_exists( 'my_music_band_testimonial_bg_css' )):
function my_music_band_testimonial_bg_css() {
	$background = get_theme_mod( 'my_music_band_testimonial_bg_image', trailingslashit( esc_url( get_template_directory_uri() ) ) . 'assets/images/testimonial-bg.jpg' );

	$css = '';

	if ( $background ) {
		$css = ' background-image: url("' . esc_url( $background ) . '");';
	}

	if ( '' !== $css ) {
		$css = '#testimonial-content-section.background-image { ' . $css . '}';
	}

	wp_add_inline_style( 'my-music-band-style', $css );
}
endif;
add_action( 'wp_enqueue_scripts', 'my_music_band_testimonial_bg_css', 11 );

/**
 * Remove first post from blog as it is already show via recent post template
 */
function my_music_band_alter_home( $query ) {
	if ( $query->is_home() && $query->is_main_query() ) {
		$cats = get_theme_mod( 'my_music_band_front_page_category' );

		if ( is_array( $cats ) && ! in_array( '0', $cats ) ) {
			$query->query_vars['category__in'] = $cats;
		}

		if ( get_theme_mod( 'my_music_band_exclude_slider_post' ) ) {
			$quantity = get_theme_mod( 'my_music_band_slider_number', 4 );

			$post_list	= array();	// list of valid post ids

			for( $i = 1; $i <= $quantity; $i++ ){
				if ( get_theme_mod( 'my_music_band_slider_post_' . $i ) && get_theme_mod( 'my_music_band_slider_post_' . $i ) > 0 ) {
					$post_list = array_merge( $post_list, array( get_theme_mod( 'my_music_band_slider_post_' . $i ) ) );
				}
			}

			if ( ! empty( $post_list ) ) {
	    		$query->query_vars['post__not_in'] = $post_list;
			}
		}
	}
}
add_action( 'pre_get_posts', 'my_music_band_alter_home' );

/**
 * Function to add Scroll Up icon
 */
function my_music_band_scrollup() {
	$disable_scrollup = get_theme_mod( 'my_music_band_disable_scrollup' );

	if ( $disable_scrollup ) {
		return;
	}

	echo '<a href="#masthead" id="scrollup" class="backtotop">' . '<span class="screen-reader-text">' . esc_html__( 'Scroll Up', 'my-music-band' ) . '</span></a>' ;

}
add_action( 'wp_footer', 'my_music_band_scrollup', 1 );

if ( ! function_exists( 'my_music_band_content_nav' ) ) :
	/**
	 * Display navigation/pagination when applicable
	 *
	 * @since My Music Band 0.1

	 */
	function my_music_band_content_nav() {
		global $wp_query;

		// Don't print empty markup in archives if there's only one page.
		if ( $wp_query->max_num_pages < 2 && ( is_home() || is_archive() || is_search() ) ) {
			return;
		}

		$pagination_type = get_theme_mod( 'my_music_band_pagination_type', 'default' );

		if ( ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'infinite-scroll' ) ) || class_exists( 'Catch_Infinite_Scroll' ) ) {
			// Support infinite scroll plugins.
			the_posts_navigation();
		} elseif ( 'numeric' === $pagination_type && function_exists( 'the_posts_pagination' ) ) {
			the_posts_pagination( array(
				'prev_text'          => '<span>' . esc_html__( 'Previous Page', 'my-music-band' ) . '</span>',
				'next_text'          => '<span>' . esc_html__( 'Next Page', 'my-music-band' ) . '</span>',
				'screen_reader_text' => '<span class="nav-subtitle screen-reader-text">' . esc_html__( 'Page', 'my-music-band' ) . ' </span>',
			) );
		} else {
			the_posts_navigation();
		}
	}
endif; // my_music_band_content_nav

/**
 * Check if a section is enabled or not based on the $value parameter
 * @param  string $value Value of the section that is to be checked
 * @return boolean return true if section is enabled otherwise false
 */
function my_music_band_check_section( $value ) {
	global $wp_query;

	// Get Page ID outside Loop
	$page_id = $wp_query->get_queried_object_id();

	// Front page displays in Reading Settings
	$page_for_posts = get_option('page_for_posts');

	return ( 'entire-site' == $value  || ( ( is_front_page() || ( is_home() && intval( $page_for_posts ) !== intval( $page_id ) ) ) && 'homepage' == $value ) );
}

/**
 * Return the first image in a post. Works inside a loop.
 * @param [integer] $post_id [Post or page id]
 * @param [string/array] $size Image size. Either a string keyword (thumbnail, medium, large or full) or a 2-item array representing width and height in pixels, e.g. array(32,32).
 * @param [string/array] $attr Query string or array of attributes.
 * @return [string] image html
 *
 * @since My Music Band 0.1

 */

function my_music_band_get_first_image( $postID, $size, $attr ) {
	ob_start();

	ob_end_clean();

	$image 	= '';

	$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', get_post_field('post_content', $postID ) , $matches);

	if( isset( $matches [1] [0] ) ) {
		//Get first image
		$first_img = $matches [1] [0];

		return '<img class="pngfix wp-post-image" src="'. esc_url( $first_img ) .'">';
	}

	return false;
}

if ( ! function_exists( 'my_music_band_get_theme_layout' ) ) :
function my_music_band_get_theme_layout() {
	$layout = '';

	if ( is_page_template( 'templates/no-sidebar.php' ) ) {
		$layout = 'no-sidebar';
	}  elseif ( is_page_template( 'templates/right-sidebar.php' ) ) {
		$layout = 'right-sidebar';
	} else {
		$layout = get_theme_mod( 'my_music_band_default_layout', 'no-sidebar' );

		if ( is_home() || is_archive() ) {
			$layout = get_theme_mod( 'my_music_band_homepage_archive_layout', 'right-sidebar' );
		}
	}

	return $layout;
}
endif;

if ( ! function_exists( 'my_music_band_get_sidebar_id' ) ) :
function my_music_band_get_sidebar_id() {
	$sidebar = '';

	$layout = my_music_band_get_theme_layout();

	$sidebaroptions = '';

	if ( 'no-sidebar-full-width' === $layout || 'no-sidebar' === $layout ) {
		return $sidebar;
	}
		global $post, $wp_query;

		// Front page displays in Reading Settings.
		$page_on_front  = get_option( 'page_on_front' );
		$page_for_posts = get_option( 'page_for_posts' );

		// Get Page ID outside Loop.
		$page_id = $wp_query->get_queried_object_id();
		// Blog Page or Front Page setting in Reading Settings.
		if ( $page_id == $page_for_posts || $page_id == $page_on_front ) {
	        $sidebaroptions = get_post_meta( $page_id, 'my-music-band-sidebar-option', true );
	    } elseif ( is_singular() ) {
	    	if ( is_attachment() ) {
				$parent 		= $post->post_parent;
				$sidebaroptions = get_post_meta( $parent, 'my-music-band-sidebar-option', true );

			} else {
				$sidebaroptions = get_post_meta( $post->ID, 'my-music-band-sidebar-option', true );
			}
		}

if ( is_active_sidebar( 'sidebar-1' ) ) {
		$sidebar = 'sidebar-1'; // Primary Sidebar.
	}

	return $sidebar;
}
endif;

if ( ! function_exists( 'my_music_band_truncate_phrase' ) ) :
	/**
	 * Return a phrase shortened in length to a maximum number of characters.
	 *
	 * Result will be truncated at the last white space in the original string. In this function the word separator is a
	 * single space. Other white space characters (like newlines and tabs) are ignored.
	 *
	 * If the first `$max_characters` of the string does not contain a space character, an empty string will be returned.
	 *
	 * @since My Music Band 0.1

	 *
	 * @param string $text            A string to be shortened.
	 * @param integer $max_characters The maximum number of characters to return.
	 *
	 * @return string Truncated string
	 */
	function my_music_band_truncate_phrase( $text, $max_characters ) {

		$text = trim( $text );

		if ( mb_strlen( $text ) > $max_characters ) {
			//* Truncate $text to $max_characters + 1
			$text = mb_substr( $text, 0, $max_characters + 1 );

			//* Truncate to the last space in the truncated string
			$text = trim( mb_substr( $text, 0, mb_strrpos( $text, ' ' ) ) );
		}

		return $text;
	}
endif; //my_music_band_truncate_phrase

if ( ! function_exists( 'my_music_band_get_the_content_limit' ) ) :
	/**
	 * Return content stripped down and limited content.
	 *
	 * Strips out tags and shortcodes, limits the output to `$max_char` characters, and appends an ellipsis and more link to the end.
	 *
	 * @since My Music Band 0.1

	 *
	 * @param integer $max_characters The maximum number of characters to return.
	 * @param string  $more_link_text Optional. Text of the more link. Default is "(more...)".
	 * @param bool    $stripteaser    Optional. Strip teaser content before the more text. Default is false.
	 *
	 * @return string Limited content.
	 */
	function my_music_band_get_the_content_limit( $max_characters, $more_link_text = '(more...)', $stripteaser = false ) {

		$content = get_the_content( '', $stripteaser );

		// Strip tags and shortcodes so the content truncation count is done correctly.
		$content = strip_tags( strip_shortcodes( $content ), apply_filters( 'get_the_content_limit_allowedtags', '<script>,<style>' ) );

		// Remove inline styles / .
		$content = trim( preg_replace( '#<(s(cript|tyle)).*?</\1>#si', '', $content ) );

		// Truncate $content to $max_char
		$content = my_music_band_truncate_phrase( $content, $max_characters );

		// More link?
		if ( $more_link_text ) {
			$link   = apply_filters( 'get_the_content_more_link', sprintf( '<span class="readmore"><a href="%s" class="more-link">%s</a></span>', esc_url( get_permalink() ), $more_link_text ), $more_link_text );
			$output = sprintf( '<p>%s %s</p>', $content, $link );
		} else {
			$output = sprintf( '<p>%s</p>', $content );
			$link = '';
		}

		return apply_filters( 'my_music_band_get_the_content_limit', $output, $content, $link, $max_characters );

	}
endif; //my_music_band_get_the_content_limit

if ( ! function_exists( 'my_music_band_content_image' ) ) :
	/**
	 * Template for Featured Image in Archive Content
	 *
	 * To override this in a child theme
	 * simply fabulous-fluid your own my_music_band_content_image(), and that function will be used instead.
	 *
	 * @since My Music Band 0.1

	 */
	function my_music_band_content_image() {
		if ( has_post_thumbnail() && my_music_band_jetpack_featured_image_display() && is_singular() ) {
			global $post, $wp_query;

			// Get Page ID outside Loop.
			$page_id = $wp_query->get_queried_object_id();

			if ( $post ) {
		 		if ( is_attachment() ) {
					$parent = $post->post_parent;

					$individual_featured_image = get_post_meta( $parent, 'my-music-band-featured-image', true );
				} else {
					$individual_featured_image = get_post_meta( $page_id, 'my-music-band-featured-image', true );
				}
			}

			if ( empty( $individual_featured_image ) ) {
				$individual_featured_image = 'default';
			}

			if ( 'disable' === $individual_featured_image ) {
				echo '<!-- Page/Post Single Image Disabled or No Image set in Post Thumbnail -->';
				return false;
			} else {
				$class = array();

				$image_size = 'post-thumbnail';

				if ( 'default' !== $individual_featured_image ) {
					$image_size = $individual_featured_image;
					$class[]    = 'from-metabox';
				} else {
					$layout = my_music_band_get_theme_layout();

					if ( 'no-sidebar-full-width' === $layout ) {
						$image_size = 'my-music-band-slider';
					}
				}

				$class[] = $individual_featured_image;
				?>
				<div class="post-thumbnail <?php echo esc_attr( implode( ' ', $class ) ); ?>">
					<a href="<?php the_permalink(); ?>">
					<?php the_post_thumbnail( $image_size ); ?>
					</a>
				</div>
		   	<?php
			}
		} // End if().
	}
endif; // my_music_band_content_image.

if ( ! function_exists( 'my_music_band_enable_homepage_posts' ) ) :
    /**
     * Determine Homepage Content disabled or not
     * @return boolean
     */
    function my_music_band_enable_homepage_posts() {
       if ( ! ( get_theme_mod( 'my_music_band_disable_homepage_posts' ) && is_front_page() ) ) {
            return true;
        }
        return false;
    }
endif; // my_music_band_enable_homepage_posts.

if ( ! function_exists( 'my_music_band_get_featured_posts' ) ) :
	/**
	 * Featured content Posts
	 */
	function my_music_band_get_featured_posts() {

		$number = get_theme_mod( 'my_music_band_featured_content_number', 3 );

		$post_list    = array();

		$args = array(
			'posts_per_page'      => $number,
			'post_type'           => 'post',
			'ignore_sticky_posts' => 1, // ignore sticky posts.
		);

		// Get valid number of posts.
			$args['post_type'] = 'featured-content';

			for ( $i = 1; $i <= $number; $i++ ) {
				$post_id = '';

					$post_id = get_theme_mod( 'my_music_band_featured_content_cpt_' . $i );

				if ( $post_id && '' !== $post_id ) {
					$post_list = array_merge( $post_list, array( $post_id ) );
				}
			}

			$args['post__in'] = $post_list;
			$args['orderby']  = 'post__in';
		$featured_posts = get_posts( $args );

		return $featured_posts;
	}
endif; // my_music_band_get_featured_posts.
