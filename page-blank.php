<?php
/**
 * Template Name: Página em branco
 * Template Post Type: page
 *
 * Canvas without the global header and footer, useful for Elementor and
 * campaign pages.
 *
 * @package DinizStudio
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dv-blank-template' ); ?>>
<?php wp_body_open(); ?>
<?php get_template_part( 'template-parts/pages/blank' ); ?>
<?php wp_footer(); ?>
</body>
</html>
