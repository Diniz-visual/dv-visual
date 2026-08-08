<?php
/**
 * Document head and global site header.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
	<style id="dv-critical-layout-gap-fix">
		/*
		 * Correção estrutural crítica carregada depois dos estilos globais do
		 * WordPress. Evita que o block-gap do editor crie faixas antes ou
		 * depois do header, sem remover o espaço legítimo da barra admin.
		 */
		body > .wp-site-blocks {
			margin-block: 0 !important;
			padding-block: 0 !important;
		}
		body > .wp-site-blocks > .dv-header,
		body > .wp-site-blocks > main,
		body > .wp-site-blocks > main#dv-main-content,
		body > .wp-site-blocks > .dv-footer {
			margin-block: 0 !important;
		}
	</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
	<a class="screen-reader-text" href="#dv-main-content"><?php esc_html_e( 'Pular para o conteúdo', 'dv-visual' ); ?></a>
	<?php get_template_part( 'template-parts/site/header' ); ?>
