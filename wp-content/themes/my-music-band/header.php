<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package My Music Band
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php do_action( 'wp_body_open' );  ?>

<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'my-music-band' ); ?></a>

	<?php get_template_part( 'template-parts/top-playlist/content', 'playlist' ); ?>

	<header id="masthead" class="site-header">
		<div class="site-header-main">
			<div class="wrapper">
				<?php get_template_part( 'template-parts/header/site', 'branding' ); ?>

				<?php get_template_part( 'template-parts/navigation/navigation', 'primary' ); ?>
			</div><!-- .wrapper -->
		</div><!-- .site-header-main -->
	</header><!-- #masthead -->

	<div class="below-site-header">

		<?php get_template_part( 'template-parts/header/breadcrumb' ); ?>

		<?php get_template_part( 'template-parts/slider/content', 'slider' ); ?>

		<?php get_template_part( 'template-parts/header/header', 'media' ); ?>

		<?php get_template_part( 'template-parts/playlist/content', 'playlist' ); ?>

		<?php get_template_part( 'template-parts/hero-content/content', 'hero' ); ?>

		<?php get_template_part('template-parts/portfolio/display','portfolio'); ?>

		<?php get_template_part( 'template-parts/testimonial/display', 'testimonial' ); ?>

		<?php get_template_part('template-parts/featured-content/display','featured'); ?>

		<div id="content" class="site-content">
			<div class="wrapper">
