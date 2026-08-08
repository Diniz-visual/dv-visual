<?php
/**
 * Shared listing for every DV Visual Custom Post Type.
 *
 * @package DinizStudio
 */

$post_type = ! empty( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : get_query_var( 'post_type' );
$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;

echo diniz_studio_cpt_archive_shortcode( array( 'post_type' => $post_type ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
