<?php
/**
 * Latest blog posts.
 *
 * PANEL: section copy in Home — Seções; article cards come from Posts.
 *
 * @package DinizStudio
 */
$section_id = ! empty( $args['section_id'] ) ? (int) $args['section_id'] : 0;
diniz_studio_render_managed_home_section( $section_id, 'posts-grid' );
