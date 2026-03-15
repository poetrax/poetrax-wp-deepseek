<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package My Music Band
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="post-wrapper hentry-inner">
		<?php my_music_band_archive_image(); ?>

		<div class="entry-container">
			<header class="entry-header">
				<?php if ( is_sticky() ) { ?>
					<span class="sticky-post"><?php esc_html_e( 'Featured', 'my-music-band' ); ?></span>
				<?php } ?>

				<?php if ( 'post' === get_post_type() ) : ?>
				<div class="entry-meta">
					<?php my_music_band_cat_list(); ?>
				</div><!-- .entry-meta -->
				<?php
				endif; ?>

				<?php
				if ( is_singular() ) :
					the_title( '<h1 class="entry-title">', '</h1>' );
				else :
					the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
				endif;?>
			</header><!-- .entry-header -->

			<div class="entry-content">
				<?php
				$archive_layout = 'excerpt-image-top';

				if ( 'full-content-image-top' === $archive_layout || 'full-content' === $archive_layout ) {
					/* translators: %s: Name of current post. Only visible to screen readers */
					the_content( sprintf(
						wp_kses(

							__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'my-music-band' ),
							array(
								'span' => array(
									'class' => array(),
								),
							)
						),
						get_the_title()
					) );

					wp_link_pages( array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'my-music-band' ),
						'after'  => '</div>',
					) );
				} else {
					the_excerpt();
				}
				?>
			</div><!-- .entry-content -->

			<div class="entry-footer">
				<?php if ( 'post' === get_post_type() ) : ?>
				<div class="entry-meta">
					<?php my_music_band_posted_on(); ?>
				</div><!-- .entry-meta -->
				<?php
				endif; ?>
			</div>
		</div><!-- .entry-container -->
	</div><!-- .hentry-inner -->
</article><!-- #post-<?php the_ID(); ?> -->