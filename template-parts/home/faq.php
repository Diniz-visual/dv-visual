<?php
/**
 * Frequently asked questions.
 *
 * PANEL: questions are managed in the Perguntas Frequentes Custom Post Type.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section(
	$section_id,
	'faq',
	function () {
		diniz_studio_render_pattern( 'faq' );
	},
	true
);
