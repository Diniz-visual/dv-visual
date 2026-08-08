<?php
/**
 * Products, tools and guides hub.
 *
 * PANEL: headings in Home — Seções; cards in Produtos, Ferramentas and Guias.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'content-hub' );
