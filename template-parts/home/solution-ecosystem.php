<?php
/**
 * Solution ecosystem.
 *
 * PANEL: edit section copy in Home — Seções and the dynamic items in Soluções.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'solution-ecosystem' );
