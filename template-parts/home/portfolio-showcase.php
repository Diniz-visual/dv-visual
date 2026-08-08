<?php
/**
 * Featured portfolio projects.
 *
 * PANEL: section copy in Home — Seções; project cards and images in Portfólio.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'portfolio-showcase' );
