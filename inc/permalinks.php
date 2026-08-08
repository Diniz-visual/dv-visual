<?php
/**
 * URL structure for native WordPress blog posts.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate the public URL for a native post as /blog/post-slug/.
 *
 * Custom Post Types are intentionally ignored so their existing archive and
 * single URLs continue to use the slugs registered in inc/post-types.php.
 *
 * @param string  $permalink Original WordPress permalink.
 * @param WP_Post $post      Post object.
 * @param bool    $leavename Whether the %postname% token should be preserved.
 * @return string
 */
function diniz_studio_blog_post_permalink( $permalink, $post, $leavename ) {
	if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return $permalink;
	}

	$post_name = $leavename ? '%postname%' : $post->post_name;
	if ( '' === $post_name ) {
		return $permalink;
	}

	return home_url( user_trailingslashit( 'blog/' . $post_name, 'single' ) );
}
add_filter( 'post_link', 'diniz_studio_blog_post_permalink', 10, 3 );

/**
 * Teach WordPress to resolve /blog/post-slug/ and the standard post endpoints.
 *
 * @return void
 */
function diniz_studio_register_blog_post_rewrites() {
	add_rewrite_rule(
		'^blog/([^/]+)/comment-page-([0-9]{1,})/?$',
		'index.php?post_type=post&name=$matches[1]&cpage=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^blog/([^/]+)/trackback/?$',
		'index.php?post_type=post&name=$matches[1]&tb=1',
		'top'
	);
	add_rewrite_rule(
		'^blog/([^/]+)/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$',
		'index.php?post_type=post&name=$matches[1]&feed=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^blog/([^/]+)/?$',
		'index.php?post_type=post&name=$matches[1]',
		'top'
	);
}
add_action( 'init', 'diniz_studio_register_blog_post_rewrites', 20 );

/**
 * Refresh the rewrite table once after this permalink feature is installed.
 *
 * This avoids requiring the administrator to manually resave
 * Settings > Permalinks after updating the theme.
 *
 * @return void
 */
function diniz_studio_maybe_flush_blog_post_rewrites() {
	$rewrite_version = '1.0.0';

	if ( $rewrite_version === get_option( 'diniz_studio_blog_rewrite_version' ) ) {
		return;
	}

	diniz_studio_register_blog_post_rewrites();
	flush_rewrite_rules( false );
	update_option( 'diniz_studio_blog_rewrite_version', $rewrite_version, false );
}
add_action( 'admin_init', 'diniz_studio_maybe_flush_blog_post_rewrites', 5 );
