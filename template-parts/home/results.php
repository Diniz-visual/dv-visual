<?php
/**
 * Legacy alias for the software carousel.
 *
 * PANEL: software cards in Softwares; section order in Home — Construtor.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section(
	$section_id,
	'softwares',
	function () {
		echo diniz_studio_software_carousel_shortcode(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	true
);
