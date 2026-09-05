<?php
/**
 * Diniz Studio theme setup.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DINIZ_STUDIO_VERSION', '4.28.9' );

require_once get_template_directory() . '/inc/acf.php';
require_once get_template_directory() . '/inc/editable-content.php';
require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/permalinks.php';
require_once get_template_directory() . '/inc/render.php';
require_once get_template_directory() . '/inc/home-manager.php';
require_once get_template_directory() . '/inc/github-updater.php';

function diniz_studio_setup() {
	load_theme_textdomain( 'dv-visual', get_template_directory() . '/languages' );
	/*
	 * WordPress enables saved block templates automatically when theme.json is
	 * present. Disable only that template loader so page-home.php, page.php and
	 * the complete PHP hierarchy remain authoritative after upgrading from the
	 * previous block-theme version.
	 */
	remove_theme_support( 'block-templates' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'               => 84,
		'width'                => 260,
		'flex-height'          => true,
		'flex-width'           => true,
		'unlink-homepage-logo' => false,
	) );
	add_theme_support( 'woocommerce' );
	add_editor_style( 'assets/css/theme.css' );
	register_nav_menus(
		array(
			'primary' => __( 'Menu principal DV', 'dv-visual' ),
			'footer'  => __( 'Menu do rodapé DV', 'dv-visual' ),
			'social'  => __( 'Redes sociais DV', 'dv-visual' ),
		)
	);
}
add_action( 'after_setup_theme', 'diniz_studio_setup' );

/**
 * Ensure WordPress has a published page assigned as the native Posts page.
 *
 * Existing assignments are preserved. On a fresh installation the theme
 * creates /blog/ once, publishes it and assigns it through page_for_posts so
 * home.php, category filters, feeds and pagination all use WordPress core.
 *
 * @return int Blog page ID, or zero when WordPress could not create it.
 */
function diniz_studio_ensure_blog_page() {
	$posts_page = (int) get_option( 'page_for_posts' );

	if ( $posts_page && 'publish' === get_post_status( $posts_page ) ) {
		update_option( 'diniz_studio_blog_page_version', DINIZ_STUDIO_VERSION, false );
		return $posts_page;
	}

	$blog_page = get_page_by_path( 'blog', OBJECT, 'page' );

	if ( $blog_page ) {
		$posts_page = (int) $blog_page->ID;
		if ( 'publish' !== $blog_page->post_status ) {
			wp_update_post(
				array(
					'ID'          => $posts_page,
					'post_status' => 'publish',
					'post_name'   => 'blog',
				)
			);
		}
	} else {
		$created = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Blog', 'dv-visual' ),
				'post_name'    => 'blog',
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $created ) ) {
			return 0;
		}

		$posts_page = (int) $created;
	}

	update_option( 'page_for_posts', $posts_page );
	update_option( 'diniz_studio_blog_page_version', DINIZ_STUDIO_VERSION, false );

	return $posts_page;
}

/**
 * Apply the Blog page setup once per theme version on existing installations.
 *
 * @return void
 */
function diniz_studio_maybe_ensure_blog_page() {
	if ( DINIZ_STUDIO_VERSION === get_option( 'diniz_studio_blog_page_version' ) ) {
		return;
	}

	diniz_studio_ensure_blog_page();
}
add_action( 'admin_init', 'diniz_studio_maybe_ensure_blog_page', 5 );

/**
 * Keep the Blog and category archives in a balanced three-by-three grid.
 *
 * WordPress still owns the query, category URLs and pagination. The theme only
 * standardizes the number of cards shown on each public archive page.
 *
 * @param WP_Query $query Current WordPress query.
 * @return void
 */
function diniz_studio_blog_posts_per_page( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_home() || $query->is_category() ) {
		$query->set( 'posts_per_page', 9 );
	}
}
add_action( 'pre_get_posts', 'diniz_studio_blog_posts_per_page' );

/**
 * Register the widget-ready columns used by the global footer.
 *
 * Each area accepts both classic widgets and the block-based WordPress widget
 * editor. Empty areas keep the curated theme fallback visible.
 *
 * @return void
 */
function diniz_studio_register_footer_widget_areas() {
	$areas = array(
		'dv-footer-brand'         => __( 'Footer 1 — Marca e contato', 'dv-visual' ),
		'dv-footer-solutions'     => __( 'Footer 2 — Soluções', 'dv-visual' ),
		'dv-footer-content'       => __( 'Footer 3 — Blog e conteúdo', 'dv-visual' ),
		'dv-footer-guides'        => __( 'Footer 4 — Guias', 'dv-visual' ),
		'dv-footer-institutional' => __( 'Footer 5 — Institucional', 'dv-visual' ),
		'dv-footer-bottom'        => __( 'Footer — Barra inferior', 'dv-visual' ),
	);

	foreach ( $areas as $id => $name ) {
		register_sidebar(
			array(
				'id'            => $id,
				'name'          => $name,
				'description'   => __( 'Adicione menus, listas, textos, imagens, ícones sociais ou qualquer bloco do WordPress.', 'dv-visual' ),
				'before_widget' => '<section id="%1$s" class="dv-footer-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="dv-footer-widget__title">',
				'after_title'   => '</h3>',
			)
		);
	}
}
add_action( 'widgets_init', 'diniz_studio_register_footer_widget_areas' );

/**
 * Return the initial menu structure used on a fresh installation.
 *
 * The generated menus remain regular WordPress navigation menus and can be
 * edited, reordered or replaced from Appearance > Menus.
 *
 * @param string $location Registered menu location.
 * @return array
 */
function diniz_studio_default_menu_blueprint( $location ) {
	$archive = static function ( $post_type, $fallback ) {
		$url = get_post_type_archive_link( $post_type );
		return $url ?: home_url( $fallback );
	};

	$blog_page = (int) get_option( 'page_for_posts' );
	$blog_url  = $blog_page ? get_permalink( $blog_page ) : home_url( '/blog/' );

	if ( 'social' === $location ) {
		return array();
	}

	$items = array(
		array( 'title' => __( 'Início', 'dv-visual' ), 'url' => home_url( '/' ) ),
		array( 'title' => __( 'Estúdio', 'dv-visual' ), 'url' => diniz_studio_menu_page_url( 'estudio' ) ),
		array( 'title' => __( 'Soluções', 'dv-visual' ), 'url' => $archive( 'service', '/solucao/' ) ),
		array( 'title' => __( 'Portfólio', 'dv-visual' ), 'url' => $archive( 'portfolio', '/portfolio/' ) ),
		array( 'title' => __( 'Produtos', 'dv-visual' ), 'url' => $archive( 'product', '/produto/' ) ),
		array(
			'title'    => __( 'Recursos', 'dv-visual' ),
			'url'      => '#',
			'children' => array(
				array( 'title' => __( 'Ferramentas', 'dv-visual' ), 'url' => $archive( 'tool', '/ferramenta/' ) ),
				array( 'title' => __( 'Guias', 'dv-visual' ), 'url' => $archive( 'guide', '/guia/' ) ),
				array( 'title' => __( 'Central de Ajuda', 'dv-visual' ), 'url' => $archive( 'help_article', '/ajuda/' ) ),
			),
		),
		array( 'title' => __( 'Journal', 'dv-visual' ), 'url' => $blog_url ),
		array( 'title' => __( 'Contato', 'dv-visual' ), 'url' => diniz_studio_menu_page_url( 'contato' ) ),
	);

	if ( 'footer' === $location ) {
		return array(
			$items[1],
			$items[2],
			$items[3],
			$items[4],
			$items[6],
			$items[7],
		);
	}

	return $items;
}

/**
 * Insert regular WordPress menu items, including nested submenu items.
 *
 * @param int   $menu_id Menu term ID.
 * @param array $items   Menu blueprint.
 * @param int   $parent  Parent menu item ID.
 * @return void
 */
function diniz_studio_insert_default_menu_items( $menu_id, $items, $parent = 0 ) {
	foreach ( $items as $item ) {
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $item['title'],
				'menu-item-url'       => $item['url'],
				'menu-item-parent-id' => $parent,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
			)
		);

		if ( is_wp_error( $item_id ) || empty( $item['children'] ) ) {
			continue;
		}

		diniz_studio_insert_default_menu_items( $menu_id, $item['children'], (int) $item_id );
	}
}

/**
 * Create and assign editable default menus without replacing user choices.
 *
 * @return void
 */
function diniz_studio_configure_default_menus() {
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	$names     = array(
		'primary' => __( 'Menu Principal DV', 'dv-visual' ),
		'footer'  => __( 'Menu do Rodapé DV', 'dv-visual' ),
		'social'  => __( 'Redes Sociais DV', 'dv-visual' ),
	);
	$changed   = false;

	foreach ( $names as $location => $menu_name ) {
		if ( ! empty( $locations[ $location ] ) ) {
			continue;
		}

		$menu    = wp_get_nav_menu_object( $menu_name );
		$menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu( $menu_name );

		if ( is_wp_error( $menu_id ) ) {
			continue;
		}

		if ( ! $menu && 'social' !== $location ) {
			diniz_studio_insert_default_menu_items(
				(int) $menu_id,
				diniz_studio_default_menu_blueprint( $location )
			);
		}

		$locations[ $location ] = (int) $menu_id;
		$changed                = true;
	}

	if ( $changed ) {
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	update_option( 'diniz_studio_setup_version', DINIZ_STUDIO_VERSION, false );
}

/**
 * Run setup once after a theme update so existing installations also receive
 * any missing menu locations.
 *
 * @return void
 */
function diniz_studio_maybe_configure_default_menus() {
	if ( DINIZ_STUDIO_VERSION === get_option( 'diniz_studio_setup_version' ) ) {
		return;
	}

	diniz_studio_configure_default_menus();
}
add_action( 'admin_init', 'diniz_studio_maybe_configure_default_menus' );

/**
 * Clarify the native WordPress custom-logo control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @return void
 */
function diniz_studio_customize_logo_control( $wp_customize ) {
	$control = $wp_customize->get_control( 'custom_logo' );
	if ( $control ) {
		$control->description = __(
			'Este logotipo será usado automaticamente no cabeçalho do site. Use preferencialmente PNG, SVG ou WebP com fundo transparente.',
			'dv-visual'
		);
	}
}
add_action( 'customize_register', 'diniz_studio_customize_logo_control', 20 );

/**
 * Allow administrators to upload transparent SVG brand assets.
 *
 * SVG remains restricted to users with unfiltered_html because it is an
 * executable XML format. The upload is sanitized again before WordPress moves
 * it into the media library.
 *
 * @param array<string,string> $mimes Allowed MIME types.
 * @return array<string,string>
 */
function diniz_studio_allow_svg_uploads( $mimes ) {
	if ( current_user_can( 'unfiltered_html' ) ) {
		$mimes['svg'] = 'image/svg+xml';
	}
	return $mimes;
}
add_filter( 'upload_mimes', 'diniz_studio_allow_svg_uploads' );

/**
 * Sanitize SVG files uploaded through the WordPress media library.
 *
 * @param array<string,mixed> $file Uploaded file information.
 * @return array<string,mixed>
 */
function diniz_studio_sanitize_svg_upload( $file ) {
	$extension = strtolower( pathinfo( isset( $file['name'] ) ? $file['name'] : '', PATHINFO_EXTENSION ) );
	if ( 'svg' !== $extension ) {
		return $file;
	}

	if ( ! current_user_can( 'unfiltered_html' ) || ! class_exists( 'DOMDocument' ) ) {
		$file['error'] = __( 'O envio de SVG está disponível somente para administradores e requer a extensão DOM do PHP.', 'dv-visual' );
		return $file;
	}

	$svg = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $svg || preg_match( '/<!DOCTYPE|<!ENTITY/i', $svg ) ) {
		$file['error'] = __( 'O arquivo SVG não é válido ou contém uma declaração insegura.', 'dv-visual' );
		return $file;
	}

	$previous_errors = libxml_use_internal_errors( true );
	$document        = new DOMDocument();
	$loaded          = $document->loadXML( $svg, LIBXML_NONET | LIBXML_NOBLANKS );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous_errors );

	if ( ! $loaded || ! $document->documentElement || 'svg' !== strtolower( $document->documentElement->localName ) ) {
		$file['error'] = __( 'O arquivo enviado não contém um SVG válido.', 'dv-visual' );
		return $file;
	}

	$xpath = new DOMXPath( $document );
	foreach ( array( 'script', 'foreignObject', 'iframe', 'object', 'embed', 'audio', 'video' ) as $blocked_tag ) {
		$blocked_nodes = $xpath->query( '//*[local-name()="' . $blocked_tag . '"]' );
		if ( $blocked_nodes ) {
			for ( $index = $blocked_nodes->length - 1; $index >= 0; $index-- ) {
				$node = $blocked_nodes->item( $index );
				if ( $node && $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}
	}

	$elements = $xpath->query( '//*' );
	if ( $elements ) {
		foreach ( $elements as $element ) {
			if ( ! $element->hasAttributes() ) {
				continue;
			}
			for ( $index = $element->attributes->length - 1; $index >= 0; $index-- ) {
				$attribute = $element->attributes->item( $index );
				$name      = strtolower( $attribute->localName );
				$value     = trim( $attribute->value );
				$unsafe    = 0 === strpos( $name, 'on' );

				if ( in_array( $name, array( 'href', 'src' ), true ) && '' !== $value && '#' !== substr( $value, 0, 1 ) && ! preg_match( '#^data:image/(?:png|gif|jpe?g|webp);base64,#i', $value ) ) {
					$unsafe = true;
				}
				if ( 'style' === $name && preg_match( '/javascript:|expression\s*\(|@import|url\s*\(/i', $value ) ) {
					$unsafe = true;
				}
				if ( $unsafe ) {
					$element->removeAttributeNode( $attribute );
				}
			}
		}
	}

	$clean_svg = $document->saveXML( $document->documentElement );
	if ( ! $clean_svg || false === file_put_contents( $file['tmp_name'], $clean_svg ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$file['error'] = __( 'Não foi possível sanitizar o arquivo SVG.', 'dv-visual' );
	}

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'diniz_studio_sanitize_svg_upload' );

function diniz_studio_activate_theme() {
	diniz_studio_register_content_types();
	diniz_studio_register_home_content_types();
	diniz_studio_register_blog_post_rewrites();
	diniz_studio_ensure_blog_page();
	diniz_studio_configure_default_menus();
	diniz_studio_seed_home_content();
	diniz_studio_seed_software_content();
	flush_rewrite_rules();
	update_option( 'diniz_studio_blog_rewrite_version', '1.0.0', false );
}
add_action( 'after_switch_theme', 'diniz_studio_activate_theme' );

function diniz_studio_assets() {
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style(
		'diniz-studio-bootstrap',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
		array(),
		'5.3.8'
	);
	wp_enqueue_style(
		'diniz-studio-swiper',
		'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
		array(),
		'11.2.10'
	);
	wp_enqueue_style(
		'diniz-studio-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array( 'diniz-studio-bootstrap', 'diniz-studio-swiper', 'dashicons' ),
		DINIZ_STUDIO_VERSION
	);
	wp_enqueue_script(
		'diniz-studio-bootstrap',
		'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
		array(),
		'5.3.8',
		true
	);
	wp_enqueue_script(
		'diniz-studio-swiper',
		'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
		array(),
		'11.2.10',
		true
	);
	wp_enqueue_script(
		'diniz-studio-interactions',
		get_template_directory_uri() . '/assets/js/theme.js',
		array( 'diniz-studio-bootstrap', 'diniz-studio-swiper' ),
		DINIZ_STUDIO_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'diniz_studio_assets' );

function diniz_studio_register_pattern_categories() {
	register_block_pattern_category( 'dv-hero', array( 'label' => __( 'DV Visual — Heróis', 'dv-visual' ) ) );
	register_block_pattern_category( 'dv-content', array( 'label' => __( 'DV Visual — Conteúdo', 'dv-visual' ) ) );
	register_block_pattern_category( 'dv-conversion', array( 'label' => __( 'DV Visual — Conversão', 'dv-visual' ) ) );
}
add_action( 'init', 'diniz_studio_register_pattern_categories' );

/**
 * Render one registered DV block pattern from a PHP template part.
 *
 * Keeping this bridge means the visual sections remain available in the block
 * editor while the public theme uses the traditional WordPress PHP hierarchy.
 *
 * @param string $slug Pattern filename without extension.
 * @return string Raw block markup, before do_blocks().
 */
function diniz_studio_pattern_source( $slug ) {
	$slug = sanitize_file_name( $slug );
	$file = locate_template( 'patterns/' . $slug . '.php', false, false );

	if ( ! $file ) {
		return '';
	}

	ob_start();
	include $file;
	return (string) ob_get_clean();
}

/**
 * Render one registered DV block pattern from a PHP template part.
 *
 * @param string $slug Pattern filename without extension.
 * @return void
 */
function diniz_studio_render_pattern( $slug ) {
	$content = diniz_studio_pattern_source( $slug );

	if ( ! $content ) {
		return;
	}

	echo do_blocks( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function diniz_studio_body_classes( $classes ) {
	$classes[] = 'dv-theme';
	$classes[] = 'dv-php-templates';
	return $classes;
}
add_filter( 'body_class', 'diniz_studio_body_classes' );
