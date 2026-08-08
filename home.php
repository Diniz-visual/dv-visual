<?php
/**
 * Posts page / blog index.
 *
 * @package DinizStudio
 */

$posts_page = (int) get_option( 'page_for_posts' );
$title      = $posts_page ? get_the_title( $posts_page ) : __( 'Journal', 'dv-visual' );

get_header();
get_template_part(
	'template-parts/archive/posts',
	null,
	array(
		'kicker'      => __( 'Conteúdo e repertório', 'dv-visual' ),
		'title'       => $title,
		'description' => __( 'Ideias, referências e decisões práticas para construir marcas relevantes.', 'dv-visual' ),
	)
);
get_footer();
