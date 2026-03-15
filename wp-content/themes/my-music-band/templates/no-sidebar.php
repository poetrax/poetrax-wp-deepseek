<?php
/*
 * Template Name: No Sidebar
 *
 * Template Post Type: post, page
 *
 * The template for displaying Page/Post with No Sidebar.
 *
 * @package My Music Band
 */

get_header(); ?>

    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <div class="singular-content-wrap">
                <?php
                while ( have_posts() ) : the_post();

                    get_template_part( 'template-parts/content/content', 'page' );

                    get_template_part( 'template-parts/content/content', 'comment' );

                endwhile; // End of the loop.
                ?>
                </div> <!-- singular-content-wrap -->
        </main><!-- #main -->
    </div><!-- #primary -->

<?php
get_footer();
