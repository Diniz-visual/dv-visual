<?php
/**
 * Client logo carousel.
 *
 * PANEL: section title/position in Home — Seções; logos in Clientes; autoplay
 * controls in DV Visual → Hero e Carrosséis.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'client-trust' );
