<?php
/**
 * Final conversion call to action.
 *
 * PANEL: title, supporting text and buttons are editable in Home — Seções.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'cta' );
