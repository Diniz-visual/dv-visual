<?php
/**
 * Home content manager.
 *
 * This file contains everything related to the two private content types used
 * to manage the Home:
 *
 * - Home — Seções: order, visibility, anchor, CSS classes and block content.
 * - Home — Slides: complete Hero slides with separate desktop/mobile media.
 *
 * Keeping this logic in one commented file makes future maintenance safer:
 * templates only render content and this file handles admin/data concerns.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the private Custom Post Types used as the Home control panel.
 *
 * They are visible in wp-admin and in the REST API (for the block editor), but
 * do not create public archives or individual URLs.
 *
 * @return void
 */
function diniz_studio_register_home_content_types() {
	register_post_type(
		'home_section',
		array(
			'labels' => array(
				'name'               => __( 'Home — Seções', 'dv-visual' ),
				'singular_name'      => __( 'Seção da Home', 'dv-visual' ),
				'add_new'            => __( 'Adicionar seção', 'dv-visual' ),
				'add_new_item'       => __( 'Adicionar seção da Home', 'dv-visual' ),
				'edit_item'          => __( 'Editar seção da Home', 'dv-visual' ),
				'new_item'           => __( 'Nova seção da Home', 'dv-visual' ),
				'all_items'          => __( 'Todas as seções', 'dv-visual' ),
				'search_items'       => __( 'Buscar seções', 'dv-visual' ),
				'not_found'          => __( 'Nenhuma seção encontrada.', 'dv-visual' ),
				'not_found_in_trash' => __( 'Nenhuma seção encontrada na lixeira.', 'dv-visual' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'dv-home-builder',
			'show_in_rest'        => true,
			'show_in_nav_menus'   => false,
			'has_archive'         => false,
			'menu_icon'           => 'dashicons-layout',
			'menu_position'       => 18,
			'supports'            => array( 'title', 'editor', 'revisions', 'custom-fields', 'page-attributes' ),
		)
	);

	register_post_type(
		'home_slide',
		array(
			'labels' => array(
				'name'               => __( 'Home — Slides', 'dv-visual' ),
				'singular_name'      => __( 'Slide da Home', 'dv-visual' ),
				'add_new'            => __( 'Adicionar slide', 'dv-visual' ),
				'add_new_item'       => __( 'Adicionar slide do Hero', 'dv-visual' ),
				'edit_item'          => __( 'Editar slide do Hero', 'dv-visual' ),
				'new_item'           => __( 'Novo slide do Hero', 'dv-visual' ),
				'all_items'          => __( 'Todos os slides', 'dv-visual' ),
				'search_items'       => __( 'Buscar slides', 'dv-visual' ),
				'not_found'          => __( 'Nenhum slide encontrado.', 'dv-visual' ),
				'not_found_in_trash' => __( 'Nenhum slide encontrado na lixeira.', 'dv-visual' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'dv-home-builder',
			'show_in_rest'        => true,
			'show_in_nav_menus'   => false,
			'has_archive'         => false,
			'menu_icon'           => 'dashicons-images-alt2',
			'menu_position'       => 19,
			'supports'            => array( 'title', 'excerpt', 'revisions', 'custom-fields', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'diniz_studio_register_home_content_types', 5 );

/**
 * Definition and initial order of all native Home sections.
 *
 * The "key" must match a filename in template-parts/home. The "pattern" is
 * copied into the block editor on first activation, preserving the initial
 * visual while making it editable.
 *
 * @return array<int,array<string,string>>
 */
function diniz_studio_default_home_sections() {
	return array(
		array( 'title' => '01 — Hero principal', 'key' => 'hero', 'pattern' => '' ),
		array( 'title' => '02 — Marcas que confiam', 'key' => 'client-trust', 'pattern' => 'client-trust' ),
		array( 'title' => '03 — O que fazemos', 'key' => 'problems', 'pattern' => 'problems' ),
		array( 'title' => '04 — Ecossistema de soluções', 'key' => 'solution-ecosystem', 'pattern' => 'solution-ecosystem' ),
		array( 'title' => '05 — Manifesto', 'key' => 'manifesto', 'pattern' => 'manifesto' ),
		array( 'title' => '06 — Projetos em destaque', 'key' => 'portfolio-showcase', 'pattern' => 'portfolio-showcase' ),
		array( 'title' => '07 — Produtos, ferramentas e guias', 'key' => 'content-hub', 'pattern' => 'content-hub' ),
		array( 'title' => '08 — Softwares utilizados', 'key' => 'softwares', 'pattern' => 'softwares' ),
		array( 'title' => '09 — Processo de trabalho', 'key' => 'process', 'pattern' => 'process' ),
		array( 'title' => '10 — Depoimentos', 'key' => 'testimonials', 'pattern' => 'testimonials' ),
		array( 'title' => '11 — Perguntas frequentes', 'key' => 'faq', 'pattern' => 'faq' ),
		array( 'title' => '12 — Conteúdos do blog', 'key' => 'posts-grid', 'pattern' => 'posts-grid' ),
		array( 'title' => '13 — Chamada final', 'key' => 'cta', 'pattern' => 'cta' ),
	);
}

/**
 * Connect dynamic Home blocks to the content types that feed them.
 *
 * A content type remains enabled when at least one of its linked blocks is
 * active. This is important for Soluções, which feeds two different Home
 * sections. Disabling both hides Soluções; keeping either one active preserves
 * it. Content is never deleted or unpublished.
 *
 * @return array<string,array<int,string>>
 */
function diniz_studio_home_section_content_type_map() {
	return array(
		'hero'               => array( 'home_slide' ),
		'client-trust'       => array( 'client' ),
		'problems'           => array( 'service' ),
		'solution-ecosystem' => array( 'service' ),
		'portfolio-showcase' => array( 'portfolio' ),
		'content-hub'        => array( 'product', 'tool', 'guide' ),
		'softwares'          => array( 'software' ),
		'results'            => array( 'software' ),
		'testimonials'       => array( 'testimonial' ),
		'faq'                => array( 'faq' ),
	);
}

/**
 * Build the active/inactive state of every managed Home block.
 *
 * Multiple sections with the same key are treated as active when at least one
 * is active. Missing sections keep their linked content types enabled so fresh
 * installations and manual migrations never lose access to content.
 *
 * @return array<string,bool>
 */
function diniz_studio_home_section_visibility_index() {
	static $states = null;

	if ( null !== $states ) {
		return $states;
	}

	$states   = array();
	$sections = get_posts(
		array(
			'post_type'      => 'home_section',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $sections as $section ) {
		$key     = sanitize_file_name( (string) diniz_studio_managed_field( $section->ID, 'dv_home_section_key', 'custom' ) );
		$enabled = 'publish' === $section->post_status && (bool) diniz_studio_managed_field( $section->ID, 'dv_home_section_enabled', 1 );
		$states[ $key ] = ! empty( $states[ $key ] ) || $enabled;
	}

	return $states;
}

/**
 * Tell whether a Home-managed content type should be visible.
 *
 * @param string $post_type WordPress post type.
 * @return bool
 */
function diniz_studio_is_home_content_type_enabled( $post_type ) {
	$linked_keys = array();

	foreach ( diniz_studio_home_section_content_type_map() as $section_key => $post_types ) {
		if ( in_array( $post_type, $post_types, true ) ) {
			$linked_keys[] = $section_key;
		}
	}

	if ( ! $linked_keys ) {
		return true;
	}

	$states = diniz_studio_home_section_visibility_index();
	$found  = false;

	foreach ( $linked_keys as $section_key ) {
		if ( ! array_key_exists( $section_key, $states ) ) {
			continue;
		}

		$found = true;
		if ( $states[ $section_key ] ) {
			return true;
		}
	}

	return ! $found;
}

/**
 * Return every content type currently hidden by the Home builder.
 *
 * @return array<int,string>
 */
function diniz_studio_inactive_home_content_types() {
	$post_types = array();

	foreach ( diniz_studio_home_section_content_type_map() as $linked_types ) {
		$post_types = array_merge( $post_types, $linked_types );
	}

	$post_types = array_values( array_unique( $post_types ) );

	return array_values(
		array_filter(
			$post_types,
			static function ( $post_type ) {
				return ! diniz_studio_is_home_content_type_enabled( $post_type );
			}
		)
	);
}

/**
 * Add state classes used to hide inactive content types without removing their
 * menu markup. Keeping the markup available lets the builder reveal an item
 * immediately when its block is reactivated.
 *
 * @param string $classes Existing admin body classes.
 * @return string
 */
function diniz_studio_home_content_admin_body_classes( $classes ) {
	foreach ( diniz_studio_inactive_home_content_types() as $post_type ) {
		$classes .= ' dv-home-post-type-' . sanitize_html_class( $post_type ) . '-inactive';
	}

	return $classes;
}
add_filter( 'admin_body_class', 'diniz_studio_home_content_admin_body_classes' );

/**
 * Hide inactive top-level custom post menus before the admin finishes painting.
 *
 * @return void
 */
function diniz_studio_home_content_admin_sidebar_styles() {
	$post_types = array_values( array_unique( array_merge( ...array_values( diniz_studio_home_section_content_type_map() ) ) ) );
	?>
	<style id="dv-home-content-admin-sidebar-visibility">
		<?php foreach ( $post_types as $post_type ) : ?>
		body.dv-home-post-type-<?php echo esc_attr( sanitize_html_class( $post_type ) ); ?>-inactive #menu-posts-<?php echo esc_attr( sanitize_html_class( $post_type ) ); ?> { display:none!important; }
		<?php endforeach; ?>
	</style>
	<?php
}
add_action( 'admin_head', 'diniz_studio_home_content_admin_sidebar_styles', 1 );

/**
 * Expose a tiny sidebar controller used on initial load and by Home Builder.
 *
 * @return void
 */
function diniz_studio_home_content_admin_sidebar_script() {
	$inactive = diniz_studio_inactive_home_content_types();
	?>
	<script id="dv-home-content-admin-sidebar-controller">
	(function () {
		window.dvSetHomeContentMenuVisibility = function (postType, visible) {
			var bodyClass = 'dv-home-post-type-' + postType + '-inactive';
			document.body.classList.toggle(bodyClass, !visible);

			var topLevel = document.getElementById('menu-posts-' + postType);
			if (topLevel) {
				topLevel.hidden = !visible;
				topLevel.setAttribute('aria-hidden', visible ? 'false' : 'true');
			}

			if ('home_slide' === postType) {
				document.querySelectorAll('#toplevel_page_dv-home-builder a[href*="post_type=home_slide"]').forEach(function (link) {
					var item = link.closest('li');
					if (item) {
						item.hidden = !visible;
						item.setAttribute('aria-hidden', visible ? 'false' : 'true');
					}
				});
			}

			document.querySelectorAll('[data-dv-linked-content-type="' + postType + '"]').forEach(function (item) {
				item.hidden = !visible;
				item.setAttribute('aria-hidden', visible ? 'false' : 'true');
			});
		};

		<?php foreach ( $inactive as $post_type ) : ?>
		window.dvSetHomeContentMenuVisibility(<?php echo wp_json_encode( $post_type ); ?>, false);
		<?php endforeach; ?>
	}());
	</script>
	<?php
}
add_action( 'admin_footer', 'diniz_studio_home_content_admin_sidebar_script', 1 );

/**
 * Prevent disabled Home content types from appearing in public queries.
 *
 * @param WP_Query $query Current query.
 * @return void
 */
function diniz_studio_hide_inactive_home_content_queries( $query ) {
	if ( is_admin() || ! $query instanceof WP_Query ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( ! $post_type ) {
		return;
	}

	$requested = (array) $post_type;
	if ( in_array( 'any', $requested, true ) ) {
		$visible = array_values( array_diff( get_post_types( array( 'public' => true ), 'names' ), diniz_studio_inactive_home_content_types() ) );
		$query->set( 'post_type', $visible );
		return;
	}

	$visible   = array_values( array_diff( $requested, diniz_studio_inactive_home_content_types() ) );

	if ( $requested === $visible ) {
		return;
	}

	if ( ! $visible ) {
		$query->set( 'post__in', array( 0 ) );
		return;
	}

	$query->set( 'post_type', 1 === count( $visible ) ? $visible[0] : $visible );
}
add_action( 'pre_get_posts', 'diniz_studio_hide_inactive_home_content_queries', 99 );

/**
 * Convert direct archives and single URLs of inactive content into real 404s.
 *
 * @return void
 */
function diniz_studio_hide_inactive_home_content_routes() {
	global $wp_query;

	foreach ( diniz_studio_inactive_home_content_types() as $post_type ) {
		if ( 'home_slide' === $post_type ) {
			continue;
		}

		if ( is_post_type_archive( $post_type ) || is_singular( $post_type ) ) {
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			return;
		}
	}
}
add_action( 'template_redirect', 'diniz_studio_hide_inactive_home_content_routes', 1 );

/**
 * Remove disabled custom posts from WordPress navigation menus.
 *
 * @param WP_Post[] $items Menu items.
 * @return WP_Post[]
 */
function diniz_studio_hide_inactive_home_content_menu_items( $items ) {
	if ( is_admin() ) {
		return $items;
	}

	$inactive = diniz_studio_inactive_home_content_types();
	$paths    = array();

	foreach ( $inactive as $post_type ) {
		$archive_url = get_post_type_archive_link( $post_type );
		if ( $archive_url ) {
			$paths[] = untrailingslashit( (string) wp_parse_url( $archive_url, PHP_URL_PATH ) );
		}
	}

	return array_values(
		array_filter(
			$items,
			static function ( $item ) use ( $inactive, $paths ) {
				if ( ! empty( $item->object ) && in_array( $item->object, $inactive, true ) ) {
					return false;
				}

				$item_path = ! empty( $item->url ) ? untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) ) : '';

				return ! $item_path || ! in_array( $item_path, $paths, true );
			}
		)
	);
}
add_filter( 'wp_nav_menu_objects', 'diniz_studio_hide_inactive_home_content_menu_items' );

/**
 * Save an ACF-compatible field value, including its field-key reference.
 *
 * Values continue to work through get_post_meta() when ACF is unavailable and
 * become formatted ACF values automatically as soon as ACF is activated.
 *
 * @param int    $post_id   Post ID.
 * @param string $name      Field name.
 * @param mixed  $value     Field value.
 * @param string $field_key ACF field key.
 * @return void
 */
function diniz_studio_update_managed_field( $post_id, $name, $value, $field_key ) {
	update_post_meta( $post_id, '_' . $name, $field_key );
	update_post_meta( $post_id, $name, $value );

	/*
	 * ACF keeps an in-memory value cache during AJAX requests. Flush only the
	 * changed field so the builder and live preview immediately read the new
	 * active/inactive state instead of the value from before the drag.
	 */
	if ( function_exists( 'acf_flush_value_cache' ) ) {
		acf_flush_value_cache( $post_id, $name );
	}
}

/**
 * Read a managed field with or without ACF.
 *
 * @param int    $post_id Post ID.
 * @param string $name    Field name.
 * @param mixed  $default Value returned when the field has never been saved.
 * @return mixed
 */
function diniz_studio_managed_field( $post_id, $name, $default = '' ) {
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $name, $post_id );

		if ( false !== $value && null !== $value && '' !== $value ) {
			return $value;
		}
	}

	if ( metadata_exists( 'post', $post_id, $name ) ) {
		return get_post_meta( $post_id, $name, true );
	}

	return $default;
}

/**
 * Create the initial editable sections and migrate the previous Hero slides.
 *
 * Existing content is never overwritten. The routine only creates defaults
 * when the corresponding Custom Post Type is completely empty.
 *
 * @return void
 */
function diniz_studio_seed_home_content() {
	$existing_sections = get_posts(
		array(
			'post_type'      => 'home_section',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! $existing_sections ) {
		foreach ( diniz_studio_default_home_sections() as $order => $section ) {
			$content = $section['pattern'] ? diniz_studio_pattern_source( $section['pattern'] ) : '';
			$post_id = wp_insert_post(
				wp_slash(
					array(
						'post_type'    => 'home_section',
						'post_status'  => 'publish',
						'post_title'   => $section['title'],
						'post_content' => $content,
						'menu_order'   => $order + 1,
					)
				)
			);

			if ( ! is_wp_error( $post_id ) && $post_id ) {
				diniz_studio_update_managed_field( $post_id, 'dv_home_section_enabled', 1, 'field_dv_home_section_enabled' );
				diniz_studio_update_managed_field( $post_id, 'dv_home_section_key', $section['key'], 'field_dv_home_section_key' );
				diniz_studio_update_managed_field( $post_id, 'dv_home_section_anchor', $section['key'], 'field_dv_home_section_anchor' );
			}
		}
	}

	$existing_slides = get_posts(
		array(
			'post_type'      => 'home_slide',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! $existing_slides ) {
		$legacy_slides = diniz_studio_content_field( 'dv_hero_slides', 'option' );
		$slides        = is_array( $legacy_slides ) && $legacy_slides ? $legacy_slides : diniz_studio_default_hero_slides();
		$field_keys    = diniz_studio_home_slide_field_keys();

		foreach ( $slides as $order => $slide ) {
			$slide   = is_array( $slide ) ? $slide : array();
			$post_id = wp_insert_post(
				array(
					'post_type'    => 'home_slide',
					'post_status'  => 'publish',
					'post_title'   => ! empty( $slide['title'] ) ? wp_strip_all_tags( $slide['title'] ) : sprintf( 'Slide %d', $order + 1 ),
					'post_excerpt' => ! empty( $slide['text'] ) ? $slide['text'] : '',
					'menu_order'   => $order + 1,
				)
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			foreach ( $field_keys as $field_name => $field_key ) {
				$legacy_name = str_replace( 'dv_home_slide_', '', $field_name );
				if ( array_key_exists( $legacy_name, $slide ) ) {
					diniz_studio_update_managed_field( $post_id, $field_name, $slide[ $legacy_name ], $field_key );
				}
			}
		}
	}

	update_option( 'diniz_studio_home_content_version', DINIZ_STUDIO_VERSION, false );
}

/**
 * Seed on theme updates as well as fresh activation.
 *
 * @return void
 */
function diniz_studio_maybe_seed_home_content() {
	if ( DINIZ_STUDIO_VERSION === get_option( 'diniz_studio_home_content_version' ) ) {
		return;
	}

	diniz_studio_seed_home_content();
}
add_action( 'admin_init', 'diniz_studio_maybe_seed_home_content' );

/**
 * Replace the former metrics section with the managed software carousel.
 * This is a deliberate one-time content migration requested for the Home.
 *
 * @return void
 */
function diniz_studio_migrate_results_to_softwares() {
	if ( '1.0.0' === get_option( 'diniz_studio_software_section_version' ) ) {
		return;
	}

	$sections = get_posts(
		array(
			'post_type'      => 'home_section',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => 'dv_home_section_key',
			'meta_value'     => 'results',
		)
	);

	foreach ( $sections as $section ) {
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $section->ID,
					'post_title'   => '08 — Softwares utilizados',
					'post_content' => diniz_studio_pattern_source( 'softwares' ),
				)
			)
		);
		diniz_studio_update_managed_field( $section->ID, 'dv_home_section_key', 'softwares', 'field_dv_home_section_key' );
		diniz_studio_update_managed_field( $section->ID, 'dv_home_section_anchor', 'softwares', 'field_dv_home_section_anchor' );
	}

	update_option( 'diniz_studio_software_section_version', '1.0.0', false );
}
add_action( 'init', 'diniz_studio_migrate_results_to_softwares', 16 );

/**
 * ACF field names and keys for one Hero slide.
 *
 * @return array<string,string>
 */
function diniz_studio_home_slide_field_keys() {
	$names = array(
		'kicker',
		'highlight',
		'text',
		'primary_cta',
		'secondary_cta',
		'image',
		'image_mobile',
		'image_position_desktop',
		'image_position_mobile',
		'background',
		'background_mobile',
		'background_position_desktop',
		'background_position_mobile',
		'overlay_opacity',
		'text_color',
		'theme',
		'project_label',
		'project_title',
		'project_category',
		'metric_value',
		'metric_label',
		'duration',
	);
	$keys  = array();

	foreach ( $names as $name ) {
		$keys[ 'dv_home_slide_' . $name ] = 'field_dv_home_slide_' . $name;
	}

	return $keys;
}

/**
 * Convert published Home — Slides posts to the array consumed by the Swiper.
 *
 * @return array<int,array<string,mixed>>
 */
function diniz_studio_get_managed_home_slides() {
	$posts = get_posts(
		array(
			'post_type'      => 'home_slide',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'order'          => 'ASC',
		)
	);

	if ( ! $posts ) {
		return array();
	}

	$slides = array();
	$fields = array_keys( diniz_studio_home_slide_field_keys() );
	$legacy = diniz_studio_content_field( 'dv_hero_slides', 'option' );
	$legacy = is_array( $legacy ) ? array_values( $legacy ) : array();
	$front  = (int) get_option( 'page_on_front' );

	foreach ( $posts as $index => $post ) {
		$legacy_slide = isset( $legacy[ $index ] ) && is_array( $legacy[ $index ] ) ? $legacy[ $index ] : array();
		$slide = array(
			'title' => get_the_title( $post ),
			'text'  => get_the_excerpt( $post ),
		);

		foreach ( $fields as $field_name ) {
			$output_name           = str_replace( 'dv_home_slide_', '', $field_name );
			$slide[ $output_name ] = diniz_studio_managed_field( $post->ID, $field_name, '' );
		}

		if ( ! $slide['text'] ) {
			$slide['text'] = get_the_excerpt( $post );
		}

		/*
		 * The former options repeater can still contain edits made before the
		 * Home — Slides manager was introduced. Use its support text only when
		 * the managed slide is empty, preserving the new editor as the primary
		 * source while making existing content visible again.
		 */
		if ( ! $slide['text'] && ! empty( $legacy_slide['text'] ) ) {
			$slide['text'] = $legacy_slide['text'];
		}

		/* The generic page Hero remains a final compatibility fallback. */
		if ( ! $slide['text'] && 0 === $index && $front ) {
			$slide['text'] = diniz_studio_content_field( 'hero_text', $front );
		}

		$slides[] = $slide;
	}

	return $slides;
}

/**
 * Get all published Home sections in their admin-defined order.
 *
 * @return WP_Post[]
 */
function diniz_studio_get_managed_home_sections() {
	return get_posts(
		array(
			'post_type'      => 'home_section',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'order'          => 'ASC',
		)
	);
}

/**
 * Render one managed Home section with clear HTML source comments.
 *
 * The section's saved block content takes priority. A pattern or callback is
 * used as a safe fallback so an accidental empty editor does not break the
 * public Home; turn off the "Exibir na Home" field to hide a section.
 *
 * @param int           $section_id      Home section post ID.
 * @param string        $fallback_pattern Pattern slug without extension.
 * @param callable|null $fallback         Dynamic renderer, used by the Hero.
 * @param bool          $force_fallback   Always use the callback (Hero slides).
 * @return void
 */
function diniz_studio_render_managed_home_section( $section_id, $fallback_pattern = '', $fallback = null, $force_fallback = false ) {
	$section       = $section_id ? get_post( $section_id ) : null;
	$section_title = $section ? get_the_title( $section ) : ucwords( str_replace( '-', ' ', $fallback_pattern ) );
	$anchor        = $section ? diniz_studio_managed_field( $section_id, 'dv_home_section_anchor', '' ) : '';
	$extra_classes = $section ? diniz_studio_managed_field( $section_id, 'dv_home_section_classes', '' ) : '';
	$anchor        = sanitize_title( (string) $anchor );
	$class_tokens  = preg_split( '/\s+/', (string) $extra_classes );
	$class_tokens  = array_filter( array_map( 'sanitize_html_class', $class_tokens ) );
	$section_key   = sanitize_html_class( $fallback_pattern ?: 'custom' );
	$base_classes  = array( 'dv-managed-home-section', 'dv-managed-home-section--' . $section_key );
	$classes       = implode( ' ', array_merge( $base_classes, $class_tokens ) );

	echo "\n<!-- DV HOME: INÍCIO — " . esc_html( $section_title ) . ' | Edite em Home — Seções -->' . "\n";
	printf(
		'<div%1$s class="%2$s" data-dv-home-section="%3$s" data-dv-home-key="%4$s">',
		$anchor ? ' id="' . esc_attr( $anchor ) . '"' : '',
		esc_attr( $classes ),
		esc_attr( $section_id ),
		esc_attr( $section_key )
	);

	$content = $section ? trim( (string) $section->post_content ) : '';

	if ( $force_fallback && is_callable( $fallback ) ) {
		call_user_func( $fallback );
	} elseif ( $content ) {
		echo apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( is_callable( $fallback ) ) {
		call_user_func( $fallback );
	} elseif ( $fallback_pattern ) {
		diniz_studio_render_pattern( $fallback_pattern );
	}

	echo "</div>\n";
	echo '<!-- DV HOME: FIM — ' . esc_html( $section_title ) . " -->\n";
}

/**
 * Make admin lists follow the "Order" value from Page Attributes.
 *
 * @param WP_Query $query Current query.
 * @return void
 */
function diniz_studio_order_home_admin_lists( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( in_array( $post_type, array( 'home_section', 'home_slide' ), true ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'ASC' ) );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'diniz_studio_order_home_admin_lists' );

/**
 * Add useful columns to Home manager screens.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function diniz_studio_home_admin_columns( $columns ) {
	$columns['dv_home_type']  = __( 'Parte da Home', 'dv-visual' );
	$columns['dv_home_order'] = __( 'Ordem', 'dv-visual' );
	$columns['dv_home_state'] = __( 'Exibição', 'dv-visual' );
	return $columns;
}
add_filter( 'manage_home_section_posts_columns', 'diniz_studio_home_admin_columns' );
add_filter( 'manage_home_slide_posts_columns', 'diniz_studio_home_admin_columns' );

/**
 * Fill the custom Home admin columns.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 * @return void
 */
function diniz_studio_home_admin_column_content( $column, $post_id ) {
	$post = get_post( $post_id );

	if ( 'dv_home_type' === $column ) {
		if ( 'home_section' === $post->post_type ) {
			echo esc_html( diniz_studio_managed_field( $post_id, 'dv_home_section_key', 'custom' ) );
		} else {
			esc_html_e( 'Hero / Swiper', 'dv-visual' );
		}
	}

	if ( 'dv_home_order' === $column ) {
		echo esc_html( (string) $post->menu_order );
	}

	if ( 'dv_home_state' === $column ) {
		$enabled = 'home_slide' === $post->post_type || (bool) diniz_studio_managed_field( $post_id, 'dv_home_section_enabled', 1 );
		echo $enabled ? '<span style="color:#07847f;font-weight:700">● Visível</span>' : '<span style="color:#8a3b3b">○ Oculta</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'manage_home_section_posts_custom_column', 'diniz_studio_home_admin_column_content', 10, 2 );
add_action( 'manage_home_slide_posts_custom_column', 'diniz_studio_home_admin_column_content', 10, 2 );

/**
 * Friendly editor guidance placed directly below the post title.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function diniz_studio_home_editor_guidance( $post ) {
	if ( 'home_section' === $post->post_type ) {
		echo '<div class="notice notice-info inline"><p><strong>Como editar:</strong> altere e arraste os blocos abaixo. Para reorganizar seções inteiras, use <a href="' . esc_url( admin_url( 'admin.php?page=dv-home-builder' ) ) . '"><strong>Home — Construtor</strong></a>. Os cards de Clientes, Soluções, Portfólio e Depoimentos continuam vindo dos respectivos menus.</p></div>';
	}

	if ( 'home_slide' === $post->post_type ) {
		echo '<div class="notice notice-info inline"><p><strong>Como editar:</strong> o título deste item é o título grande do slide. Complete os textos, botões, imagens desktop/mobile e demais opções nos campos abaixo. Use “Ordem” para organizar o carrossel.</p></div>';
	}
}
add_action( 'edit_form_after_title', 'diniz_studio_home_editor_guidance' );

/**
 * Customize title placeholders on the two Home screens.
 *
 * @param string  $placeholder Default placeholder.
 * @param WP_Post $post        Current post.
 * @return string
 */
function diniz_studio_home_title_placeholder( $placeholder, $post ) {
	if ( 'home_section' === $post->post_type ) {
		return __( 'Nome administrativo da seção', 'dv-visual' );
	}

	if ( 'home_slide' === $post->post_type ) {
		return __( 'Título principal exibido no slide', 'dv-visual' );
	}

	return $placeholder;
}
add_filter( 'enter_title_here', 'diniz_studio_home_title_placeholder', 10, 2 );

/**
 * --------------------------------------------------------------------------
 * VISUAL HOME BUILDER
 * --------------------------------------------------------------------------
 *
 * Whole Home sections become draggable cards in wp-admin. Opening a card uses
 * the native WordPress block editor, where its internal blocks can also be
 * dragged, nested, duplicated and styled.
 */

/**
 * Add the visual builder as the parent menu for sections and Hero slides.
 *
 * @return void
 */
function diniz_studio_register_home_builder_menu() {
	add_menu_page(
		__( 'Construtor visual da Home', 'dv-visual' ),
		__( 'Home — Construtor', 'dv-visual' ),
		'edit_pages',
		'dv-home-builder',
		'diniz_studio_render_home_builder',
		'dashicons-layout',
		18
	);
}
add_action( 'admin_menu', 'diniz_studio_register_home_builder_menu', 9 );

/**
 * Labels and icons used by the visual library.
 *
 * @return array<string,array<string,string>>
 */
function diniz_studio_home_builder_types() {
	return array(
		'hero'               => array( 'label' => __( 'Hero principal', 'dv-visual' ), 'icon' => 'dashicons-images-alt2' ),
		'client-trust'       => array( 'label' => __( 'Marcas que confiam', 'dv-visual' ), 'icon' => 'dashicons-groups' ),
		'problems'           => array( 'label' => __( 'O que fazemos', 'dv-visual' ), 'icon' => 'dashicons-admin-tools' ),
		'solution-ecosystem' => array( 'label' => __( 'Ecossistema de soluções', 'dv-visual' ), 'icon' => 'dashicons-screenoptions' ),
		'manifesto'          => array( 'label' => __( 'Manifesto', 'dv-visual' ), 'icon' => 'dashicons-format-quote' ),
		'portfolio-showcase' => array( 'label' => __( 'Projetos em destaque', 'dv-visual' ), 'icon' => 'dashicons-portfolio' ),
		'content-hub'        => array( 'label' => __( 'Produtos, ferramentas e guias', 'dv-visual' ), 'icon' => 'dashicons-grid-view' ),
		'softwares'          => array( 'label' => __( 'Softwares utilizados', 'dv-visual' ), 'icon' => 'dashicons-desktop' ),
		'results'            => array( 'label' => __( 'Softwares utilizados (compatibilidade)', 'dv-visual' ), 'icon' => 'dashicons-desktop' ),
		'process'            => array( 'label' => __( 'Processo de trabalho', 'dv-visual' ), 'icon' => 'dashicons-list-view' ),
		'testimonials'       => array( 'label' => __( 'Depoimentos', 'dv-visual' ), 'icon' => 'dashicons-testimonial' ),
		'faq'                => array( 'label' => __( 'Perguntas frequentes', 'dv-visual' ), 'icon' => 'dashicons-editor-help' ),
		'posts-grid'         => array( 'label' => __( 'Conteúdos do blog', 'dv-visual' ), 'icon' => 'dashicons-welcome-write-blog' ),
		'cta'                => array( 'label' => __( 'Chamada final', 'dv-visual' ), 'icon' => 'dashicons-megaphone' ),
		'custom'             => array( 'label' => __( 'Seção personalizada', 'dv-visual' ), 'icon' => 'dashicons-plus-alt2' ),
	);
}

/**
 * Return all non-trashed sections for the builder canvas.
 *
 * @return WP_Post[]
 */
function diniz_studio_get_home_builder_sections() {
	return get_posts(
		array(
			'post_type'      => 'home_section',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'order'          => 'ASC',
		)
	);
}

/**
 * Render one draggable section card inside the active or inactive column.
 *
 * @param WP_Post                           $section Section post.
 * @param int                               $index   Position inside its column.
 * @param bool                              $active  Whether it is visible.
 * @param array<string,array<string,string>> $types  Builder type definitions.
 * @return void
 */
function diniz_studio_render_home_builder_card( $section, $index, $active, $types ) {
	$key        = (string) diniz_studio_managed_field( $section->ID, 'dv_home_section_key', 'custom' );
	$type       = isset( $types[ $key ] ) ? $types[ $key ] : $types['custom'];
	$edit_url   = get_edit_post_link( $section->ID, 'raw' );
	$post_state = 'publish' === $section->post_status ? __( 'Publicada', 'dv-visual' ) : __( 'Rascunho', 'dv-visual' );
	$toggle     = $active ? __( 'Mover para Todos os blocos', 'dv-visual' ) : __( 'Ativar na Home', 'dv-visual' );
	?>
	<li class="dv-home-builder__card<?php echo $active ? ' is-active' : ' is-inactive'; ?>" data-section-id="<?php echo esc_attr( $section->ID ); ?>" data-section-key="<?php echo esc_attr( $key ); ?>" data-section-active="<?php echo $active ? 'true' : 'false'; ?>" tabindex="-1">
		<button class="dv-home-builder__drag" type="button" draggable="true" aria-label="<?php echo esc_attr( sprintf( __( 'Arrastar %s', 'dv-visual' ), get_the_title( $section ) ) ); ?>">
			<span class="dashicons dashicons-move" aria-hidden="true"></span>
		</button>
		<span class="dv-home-builder__order"><?php echo $active ? esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) : '—'; ?></span>
		<span class="dv-home-builder__icon"><span class="dashicons <?php echo esc_attr( $type['icon'] ); ?>" aria-hidden="true"></span></span>
		<div class="dv-home-builder__card-copy">
			<strong><?php echo esc_html( get_the_title( $section ) ); ?></strong>
			<span><?php echo esc_html( $type['label'] ); ?> · <?php echo esc_html( $post_state ); ?></span>
		</div>
		<span class="dv-home-builder__visibility">
			<span class="dv-home-builder__visibility-dot" aria-hidden="true"></span>
			<span class="dv-home-builder__visibility-label"><?php echo $active ? esc_html__( 'Na Home', 'dv-visual' ) : esc_html__( 'Inativo', 'dv-visual' ); ?></span>
		</span>
		<div class="dv-home-builder__card-actions">
			<button type="button" class="dv-home-builder__icon-button" data-move="up" aria-label="<?php esc_attr_e( 'Mover para cima', 'dv-visual' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
			<button type="button" class="dv-home-builder__icon-button" data-move="down" aria-label="<?php esc_attr_e( 'Mover para baixo', 'dv-visual' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
			<button type="button" class="dv-home-builder__icon-button" data-toggle-section aria-pressed="<?php echo $active ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $toggle ); ?>"><span class="dashicons <?php echo $active ? 'dashicons-remove' : 'dashicons-plus-alt2'; ?>" aria-hidden="true"></span></button>
			<a class="dv-home-builder__edit" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'dv-visual' ); ?> <span aria-hidden="true">↗</span></a>
		</div>
	</li>
	<?php
}

/**
 * Render the Home builder interface.
 *
 * @return void
 */
function diniz_studio_render_home_builder() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'Você não tem permissão para editar a Home.', 'dv-visual' ) );
	}

	$sections      = diniz_studio_get_home_builder_sections();
	$types         = diniz_studio_home_builder_types();
	$existing_keys = array();
	$active_sections   = array();
	$inactive_sections = array();
	$home_url      = home_url( '/' );
	$preview_url   = add_query_arg( 'dv_builder_preview', '1', $home_url );

	foreach ( $sections as $section ) {
		$existing_keys[] = (string) diniz_studio_managed_field( $section->ID, 'dv_home_section_key', 'custom' );
		if ( (bool) diniz_studio_managed_field( $section->ID, 'dv_home_section_enabled', 1 ) ) {
			$active_sections[] = $section;
		} else {
			$inactive_sections[] = $section;
		}
	}
	?>
	<div class="wrap dv-home-builder">
		<header class="dv-home-builder__header">
			<div>
				<p class="dv-home-builder__eyebrow"><?php esc_html_e( 'DV VISUAL · WORDPRESS', 'dv-visual' ); ?></p>
				<h1><?php esc_html_e( 'Construtor visual da Home', 'dv-visual' ); ?></h1>
				<p><?php esc_html_e( 'Arraste para “Blocos ativos” para exibir na Home. Ao desativar um bloco dinâmico, o custom post relacionado também fica oculto sem apagar seus conteúdos.', 'dv-visual' ); ?></p>
			</div>
			<div class="dv-home-builder__header-actions">
				<a class="dv-builder-button dv-builder-button--ghost" data-dv-linked-content-type="home_slide" href="<?php echo esc_url( admin_url( 'edit.php?post_type=home_slide' ) ); ?>">
					<span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>
					<?php esc_html_e( 'Slides do Hero', 'dv-visual' ); ?>
				</a>
				<a class="dv-builder-button dv-builder-button--ghost" href="<?php echo esc_url( $home_url ); ?>" target="_blank" rel="noopener">
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
					<?php esc_html_e( 'Abrir Home', 'dv-visual' ); ?>
				</a>
				<button class="dv-builder-button dv-builder-button--primary" id="dv-builder-add" type="button">
					<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
					<?php esc_html_e( 'Adicionar seção', 'dv-visual' ); ?>
				</button>
			</div>
		</header>

		<div class="dv-home-builder__notice" id="dv-builder-notice" role="status" aria-live="polite"></div>

		<div class="dv-home-builder__workspace">
			<section class="dv-home-builder__panel" aria-labelledby="dv-builder-sections-title">
				<div class="dv-home-builder__panel-heading">
					<div>
						<span><?php echo esc_html( sprintf( _n( '%d seção', '%d seções', count( $sections ), 'dv-visual' ), count( $sections ) ) ); ?></span>
						<h2 id="dv-builder-sections-title"><?php esc_html_e( 'Organize a página', 'dv-visual' ); ?></h2>
					</div>
					<span class="dv-home-builder__save-state" id="dv-builder-save-state"><?php esc_html_e( 'Tudo salvo', 'dv-visual' ); ?></span>
				</div>

				<div class="dv-home-builder__boards">
					<section class="dv-home-builder__lane is-active" data-home-lane="active">
						<header>
							<div>
								<span class="dv-home-builder__lane-status"><i aria-hidden="true"></i><?php esc_html_e( 'VISÍVEIS NO SITE', 'dv-visual' ); ?></span>
								<h3><?php esc_html_e( 'Blocos ativos', 'dv-visual' ); ?></h3>
								<p><?php esc_html_e( 'A ordem abaixo é a ordem exibida na Home.', 'dv-visual' ); ?></p>
							</div>
							<b data-active-count><?php echo esc_html( count( $active_sections ) ); ?></b>
						</header>
						<ol class="dv-home-builder__sections" id="dv-home-active" data-home-zone="active" aria-label="<?php esc_attr_e( 'Blocos ativos da Home', 'dv-visual' ); ?>">
							<?php foreach ( $active_sections as $index => $section ) : ?>
								<?php diniz_studio_render_home_builder_card( $section, $index, true, $types ); ?>
							<?php endforeach; ?>
						</ol>
						<div class="dv-home-builder__drop-hint"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'Solte aqui para exibir na Home', 'dv-visual' ); ?></div>
					</section>

					<section class="dv-home-builder__lane is-inactive" data-home-lane="inactive">
						<header>
							<div>
								<span class="dv-home-builder__lane-status"><i aria-hidden="true"></i><?php esc_html_e( 'OCULTOS DO SITE', 'dv-visual' ); ?></span>
								<h3><?php esc_html_e( 'Todos os blocos', 'dv-visual' ); ?></h3>
								<p><?php esc_html_e( 'O bloco e seu custom post ficam ocultos e podem ser reativados quando quiser.', 'dv-visual' ); ?></p>
							</div>
							<b data-inactive-count><?php echo esc_html( count( $inactive_sections ) ); ?></b>
						</header>
						<ol class="dv-home-builder__sections" id="dv-home-inactive" data-home-zone="inactive" aria-label="<?php esc_attr_e( 'Blocos inativos da Home', 'dv-visual' ); ?>">
							<?php foreach ( $inactive_sections as $index => $section ) : ?>
								<?php diniz_studio_render_home_builder_card( $section, $index, false, $types ); ?>
							<?php endforeach; ?>
						</ol>
						<div class="dv-home-builder__drop-hint"><span class="dashicons dashicons-hidden" aria-hidden="true"></span><?php esc_html_e( 'Solte aqui para ocultar da Home', 'dv-visual' ); ?></div>
					</section>
				</div>
			</section>

			<section class="dv-home-builder__preview" aria-labelledby="dv-builder-preview-title">
				<div class="dv-home-builder__preview-bar">
					<div>
						<span><?php esc_html_e( 'Prévia ao vivo', 'dv-visual' ); ?></span>
						<h2 id="dv-builder-preview-title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
					</div>
					<div class="dv-home-builder__devices" role="group" aria-label="<?php esc_attr_e( 'Tamanho da prévia', 'dv-visual' ); ?>">
						<button type="button" class="is-active" data-preview-device="desktop" aria-label="<?php esc_attr_e( 'Desktop', 'dv-visual' ); ?>"><span class="dashicons dashicons-desktop" aria-hidden="true"></span></button>
						<button type="button" data-preview-device="tablet" aria-label="<?php esc_attr_e( 'Tablet', 'dv-visual' ); ?>"><span class="dashicons dashicons-tablet" aria-hidden="true"></span></button>
						<button type="button" data-preview-device="mobile" aria-label="<?php esc_attr_e( 'Celular', 'dv-visual' ); ?>"><span class="dashicons dashicons-smartphone" aria-hidden="true"></span></button>
						<button type="button" data-refresh-preview aria-label="<?php esc_attr_e( 'Atualizar prévia', 'dv-visual' ); ?>"><span class="dashicons dashicons-update" aria-hidden="true"></span></button>
					</div>
				</div>
				<div class="dv-home-builder__frame-shell" data-preview-shell="desktop">
					<iframe id="dv-home-preview" src="<?php echo esc_url( $preview_url ); ?>" title="<?php esc_attr_e( 'Prévia da página inicial', 'dv-visual' ); ?>"></iframe>
				</div>
			</section>
		</div>

		<div class="dv-home-builder__modal" id="dv-builder-library" hidden aria-hidden="true">
			<div class="dv-home-builder__modal-backdrop" data-close-library></div>
			<section class="dv-home-builder__library" role="dialog" aria-modal="true" aria-labelledby="dv-builder-library-title">
				<header>
					<div>
						<p><?php esc_html_e( 'BIBLIOTECA DA HOME', 'dv-visual' ); ?></p>
						<h2 id="dv-builder-library-title"><?php esc_html_e( 'Adicionar uma seção', 'dv-visual' ); ?></h2>
					</div>
					<button type="button" data-close-library aria-label="<?php esc_attr_e( 'Fechar biblioteca', 'dv-visual' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
				</header>
				<div class="dv-home-builder__library-grid">
					<?php foreach ( $types as $key => $type ) : ?>
						<?php $already_used = 'custom' !== $key && in_array( $key, $existing_keys, true ); ?>
						<button type="button" data-create-section="<?php echo esc_attr( $key ); ?>"<?php disabled( $already_used ); ?>>
							<span class="dv-home-builder__library-icon"><span class="dashicons <?php echo esc_attr( $type['icon'] ); ?>" aria-hidden="true"></span></span>
							<strong><?php echo esc_html( $type['label'] ); ?></strong>
							<small><?php echo $already_used ? esc_html__( 'Já adicionada', 'dv-visual' ) : esc_html__( 'Adicionar à página', 'dv-visual' ); ?></small>
						</button>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
	</div>
	<?php
}

/**
 * Load builder-only styles and behavior.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function diniz_studio_enqueue_home_builder_assets( $hook ) {
	if ( 'toplevel_page_dv-home-builder' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'diniz-studio-home-builder',
		get_template_directory_uri() . '/assets/css/home-builder.css',
		array( 'dashicons' ),
		DINIZ_STUDIO_VERSION
	);
	wp_enqueue_script(
		'diniz-studio-home-builder',
		get_template_directory_uri() . '/assets/js/home-builder.js',
		array(),
		DINIZ_STUDIO_VERSION,
		true
	);
	wp_localize_script(
		'diniz-studio-home-builder',
		'DVHomeBuilder',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'diniz_studio_home_builder' ),
			'saving'       => __( 'Salvando…', 'dv-visual' ),
			'saved'        => __( 'Tudo salvo', 'dv-visual' ),
			'error'        => __( 'Não foi possível salvar. Tente novamente.', 'dv-visual' ),
			'visible'      => __( 'Na Home', 'dv-visual' ),
			'hidden'       => __( 'Inativo', 'dv-visual' ),
			'hideSection'  => __( 'Mover para Todos os blocos', 'dv-visual' ),
			'showSection'  => __( 'Ativar na Home', 'dv-visual' ),
			'creating'     => __( 'Adicionando seção…', 'dv-visual' ),
			'sectionAdded' => __( 'Seção adicionada.', 'dv-visual' ),
			'sectionContentTypes' => diniz_studio_home_section_content_type_map(),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'diniz_studio_enqueue_home_builder_assets' );

/**
 * Verify nonce and capability for every builder mutation.
 *
 * @return void
 */
function diniz_studio_verify_home_builder_request() {
	check_ajax_referer( 'diniz_studio_home_builder', 'nonce' );

	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permissão insuficiente.', 'dv-visual' ) ), 403 );
	}
}

/**
 * Persist the two connected columns.
 *
 * Kept separate from the AJAX controller so the data behavior can be tested
 * directly and reused by future REST or command-line integrations.
 *
 * @param array<int,int|string> $active_order   Visible section IDs.
 * @param array<int,int|string> $inactive_order Hidden section IDs.
 * @return array<string,int>
 */
function diniz_studio_save_home_builder_layout( $active_order, $inactive_order ) {
	$active_order   = array_values( array_unique( array_map( 'absint', $active_order ) ) );
	$inactive_order = array_values( array_unique( array_map( 'absint', $inactive_order ) ) );
	$inactive_order = array_values( array_diff( $inactive_order, $active_order ) );

	foreach ( $active_order as $index => $post_id ) {
		$post_id = absint( $post_id );
		if ( 'home_section' !== get_post_type( $post_id ) ) {
			continue;
		}

		wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => $index + 1,
			)
		);
		diniz_studio_update_managed_field( $post_id, 'dv_home_section_enabled', 1, 'field_dv_home_section_enabled' );
	}

	$inactive_start = count( $active_order );
	foreach ( $inactive_order as $index => $post_id ) {
		if ( 'home_section' !== get_post_type( $post_id ) ) {
			continue;
		}

		wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => $inactive_start + $index + 1,
			)
		);
		diniz_studio_update_managed_field( $post_id, 'dv_home_section_enabled', 0, 'field_dv_home_section_enabled' );
	}

	return array(
		'activeCount'   => count( $active_order ),
		'inactiveCount' => count( $inactive_order ),
	);
}

/**
 * Save active/inactive columns, visibility and public Home order together.
 *
 * @return void
 */
function diniz_studio_ajax_save_home_builder_order() {
	diniz_studio_verify_home_builder_request();

	$active_order   = isset( $_POST['activeOrder'] ) ? json_decode( wp_unslash( $_POST['activeOrder'] ), true ) : array();
	$inactive_order = isset( $_POST['inactiveOrder'] ) ? json_decode( wp_unslash( $_POST['inactiveOrder'] ), true ) : array();

	/*
	 * "order" keeps compatibility with version 4.1 requests while the two new
	 * arrays are the authoritative layout used by the dual-column builder.
	 */
	if ( ! $active_order && isset( $_POST['order'] ) ) {
		$active_order = json_decode( wp_unslash( $_POST['order'] ), true );
	}

	if ( ! is_array( $active_order ) || ! is_array( $inactive_order ) ) {
		wp_send_json_error( array( 'message' => __( 'Ordem inválida.', 'dv-visual' ) ), 400 );
	}

	wp_send_json_success( diniz_studio_save_home_builder_layout( $active_order, $inactive_order ) );
}
add_action( 'wp_ajax_diniz_studio_save_home_builder_order', 'diniz_studio_ajax_save_home_builder_order' );

/**
 * Show or hide one section without deleting it.
 *
 * @return void
 */
function diniz_studio_ajax_toggle_home_section() {
	diniz_studio_verify_home_builder_request();

	$post_id = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
	$enabled = ! empty( $_POST['enabled'] ) ? 1 : 0;

	if ( 'home_section' !== get_post_type( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Seção inválida.', 'dv-visual' ) ), 400 );
	}

	diniz_studio_update_managed_field( $post_id, 'dv_home_section_enabled', $enabled, 'field_dv_home_section_enabled' );
	wp_send_json_success( array( 'enabled' => (bool) $enabled ) );
}
add_action( 'wp_ajax_diniz_studio_toggle_home_section', 'diniz_studio_ajax_toggle_home_section' );

/**
 * Add a predefined section from the visual library.
 *
 * @return void
 */
function diniz_studio_ajax_create_home_section() {
	diniz_studio_verify_home_builder_request();

	$key   = isset( $_POST['sectionKey'] ) ? sanitize_file_name( wp_unslash( $_POST['sectionKey'] ) ) : '';
	$types = diniz_studio_home_builder_types();

	if ( ! isset( $types[ $key ] ) ) {
		wp_send_json_error( array( 'message' => __( 'Tipo de seção inválido.', 'dv-visual' ) ), 400 );
	}

	if ( 'custom' !== $key ) {
		$existing = get_posts(
			array(
				'post_type'      => 'home_section',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => 'dv_home_section_key',
				'meta_value'     => $key,
			)
		);

		if ( $existing ) {
			wp_send_json_error( array( 'message' => __( 'Esta seção já está na Home.', 'dv-visual' ) ), 409 );
		}
	}

	$pattern = '';
	foreach ( diniz_studio_default_home_sections() as $definition ) {
		if ( $key === $definition['key'] ) {
			$pattern = $definition['pattern'];
			break;
		}
	}

	$sections  = diniz_studio_get_home_builder_sections();
	$max_order = 0;
	foreach ( $sections as $section ) {
		$max_order = max( $max_order, (int) $section->menu_order );
	}

	$post_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'home_section',
				'post_status'  => 'publish',
				'post_title'   => $types[ $key ]['label'],
				'post_content' => $pattern ? diniz_studio_pattern_source( $pattern ) : '',
				'menu_order'   => $max_order + 1,
			)
		)
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		wp_send_json_error( array( 'message' => __( 'Não foi possível criar a seção.', 'dv-visual' ) ), 500 );
	}

	$anchor = 'custom' === $key ? 'secao-' . $post_id : $key;
	diniz_studio_update_managed_field( $post_id, 'dv_home_section_enabled', 1, 'field_dv_home_section_enabled' );
	diniz_studio_update_managed_field( $post_id, 'dv_home_section_key', $key, 'field_dv_home_section_key' );
	diniz_studio_update_managed_field( $post_id, 'dv_home_section_anchor', $anchor, 'field_dv_home_section_anchor' );

	wp_send_json_success(
		array(
			'postId'  => $post_id,
			'editUrl' => get_edit_post_link( $post_id, 'raw' ),
		)
	);
}
add_action( 'wp_ajax_diniz_studio_create_home_section', 'diniz_studio_ajax_create_home_section' );

/**
 * Remove the WordPress admin bar only inside the builder iframe preview.
 *
 * @param bool $show Whether the toolbar would normally be displayed.
 * @return bool
 */
function diniz_studio_home_builder_preview_admin_bar( $show ) {
	if ( isset( $_GET['dv_builder_preview'] ) && current_user_can( 'edit_pages' ) ) {
		return false;
	}

	return $show;
}
add_filter( 'show_admin_bar', 'diniz_studio_home_builder_preview_admin_bar' );
