<?php
/**
 * Software carousel.
 *
 * PANEL: cards in Softwares; heading and autoplay in DV Visual → Hero e
 * Carrosséis; order and visibility in Home — Construtor.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'softwares' );
