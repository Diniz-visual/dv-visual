<?php
/**
 * Required final fallback for the WordPress PHP template hierarchy.
 *
 * @package DinizStudio
 */

get_header();
get_template_part(
	'template-parts/archive/posts',
	null,
	array(
		'kicker'      => __( 'Journal', 'dv-visual' ),
		'title'       => __( 'Ideias para marcas em movimento.', 'dv-visual' ),
		'description' => __( 'Ideias, referências e decisões práticas para construir marcas relevantes.', 'dv-visual' ),
	)
);
get_footer();
