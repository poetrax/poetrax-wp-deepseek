<?php
/**
 * The template used for displaying testimonial on front page
 *
 * @package My Music Band
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="hentry-inner">
		<div class="entry-container">
			<?php
			$show_content = get_theme_mod( 'my_music_band_testimonial_show', 'full-content' );

			if ( 'excerpt' === $show_content  ) : ?>
			<div class="entry-content">
				<?php the_excerpt(); ?>
			</div>
			<?php elseif ( 'full-content' === $show_content ) : ?>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
			<?php endif; ?>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="post-thumbnail testimonial-thumbnail">
					<?php the_post_thumbnail( 'my-music-band-testimonial' ); ?>
				</div>
			<?php endif; ?>

			<?php $position = get_post_meta( get_the_id(), 'ect_testimonial_position', true ); ?>

			<?php if ( $position ) : ?>
				<header class="entry-header">
					<?php
						the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '">', '</a></h2>' );

					?>

					<?php if ( $position ) : ?>
						<p class="entry-meta"><span class="position"><?php echo esc_html( $position ); ?></span></p>
					<?php endif; ?>
				</header>
			<?php endif;?>
		</div><!-- .entry-container -->
	</div><!-- .hentry-inner -->
</article>
