<?php
/**
 * Categories, tags, authors and date archives.
 *
 * @package DinizStudio
 */

get_header();
get_template_part(
	'template-parts/archive/posts',
	null,
	array(
		'kicker'      => __( 'Explore nossos conteúdos', 'dv-visual' ),
		'title'       => get_the_archive_title(),
		'description' => get_the_archive_description(),
	)
);
get_footer();
