<?php
/**
 * Search results.
 *
 * @package DinizStudio
 */

get_header();
get_template_part(
	'template-parts/archive/posts',
	null,
	array(
		'kicker'      => __( 'Busca', 'dv-visual' ),
		'title'       => sprintf( __( 'Resultados para “%s”', 'dv-visual' ), get_search_query() ),
		'description' => __( 'Conteúdos encontrados em todo o site.', 'dv-visual' ),
	)
);
get_footer();
