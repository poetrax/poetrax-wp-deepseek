<?php
/**
 * Template part for displaying Recent Posts in the front page template
 *
 * @package My Music Band
 */
?>
<div class="recent-blog-content archive-posts-wrapper section">
	<div class="wrapper">
		<?php
		$post_title = 'Recent Posts'; ?>

			<div class="section-heading-wrapper">
				<h2 class="section-title"><?php echo esc_html( $post_title ); ?></h2>
			</div><!-- .section-heading-wrap -->
		<div class="section-content-wrapper recent-blog-content-wrapper">
			<div id="infinite-post-wrap" class="archive-post-wrap">
				<?php
				$recent_posts = new WP_Query( array(
					'ignore_sticky_posts' => true,
				) );

				/* Start the Loop */
				while ( $recent_posts->have_posts() ) :
					$recent_posts->the_post();
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
							</div> <!-- .entry-container -->
						</div><!-- .hentry-inner -->
					</article><!-- #post -->
					<?php
				endwhile;

				wp_reset_postdata();
				?>
			</div><!-- .archive-post-wrap -->
		</div><!-- .section-content-wrap -->
		<p class="view-more"><a class="more-recent-posts button" href="<?php the_permalink( get_option( 'page_for_posts' ) ); ?>"><?php esc_html_e( 'More Posts', 'my-music-band' ); ?></a></p>
	</div> <!-- .wrapper -->
</div> <!-- .recent-blog-content-wrapper -->
