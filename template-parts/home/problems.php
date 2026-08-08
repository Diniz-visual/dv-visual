<?php
/**
 * Positioning and services introduction.
 *
 * PANEL: headings and layout in Home — Seções; service cards in Soluções.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'problems' );
