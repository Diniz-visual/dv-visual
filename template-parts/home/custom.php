<?php
/**
 * Custom Home section.
 *
 * Use this renderer when "Tipo da seção" is set to "Seção personalizada".
 * Everything saved in the Home — Seções block editor is rendered here.
 *
 * @package DinizStudio
 */

$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id );
