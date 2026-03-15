<?php
/**
 * The template part for displaying an Author biography
 *
 * @package My Music Band
 */
?>

<div class="author-info">
	<div class="author-avatar">
		<?php
		/**
		 * Filter the My Music Band author bio avatar size.
		 *
		 * @since My Music Band 0.1

		 *
		 * @param int $size The avatar height and width size in pixels.
		 */
		$author_bio_avatar_size = apply_filters( 'my_music_band_author_bio_avatar_size', 105 );

		echo get_avatar( get_the_author_meta( 'user_email' ), $author_bio_avatar_size );
		?>
	</div><!-- .author-avatar -->

	<div class="author-description">
		<h2 class="author-title"><span class="author-heading screen-reader-text"><?php esc_html_e( 'Author:', 'my-music-band' ); ?></span> <?php echo get_the_author(); ?></h2>

		<p class="author-bio">
			<?php the_author_meta( 'description' ); ?>
		</p><!-- .author-bio -->

		<a class="author-link" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
			<?php printf( __( 'View all posts by %s', 'my-music-band' ), get_the_author() ); ?>
		</a>
	</div><!-- .author-description -->
</div><!-- .author-info -->
