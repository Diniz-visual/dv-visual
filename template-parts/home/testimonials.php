<?php
/**
 * Testimonial carousel.
 *
 * PANEL: section text in Home — Seções; each testimonial in Depoimentos.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'testimonials' );
