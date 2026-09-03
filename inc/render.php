<?php
/**
 * Front-end helpers and ACF output.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a global theme option while keeping sensible defaults without ACF.
 *
 * @param string $name    ACF option field name.
 * @param mixed  $default Value used before the field is saved or without ACF.
 * @return mixed
 */
function diniz_studio_global_option( $name, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $name, 'option' );
	return null === $value || '' === $value ? $default : $value;
}

/**
 * Build the native breadcrumb trail for the current WordPress request.
 *
 * Templates may still supply a hand-crafted trail when a special hierarchy is
 * required. This fallback keeps Pages, Blog, taxonomies, searches, archives,
 * Custom Post Types and the 404 page consistent across the entire site.
 *
 * @return array<int,array{label:string,url?:string}>
 */
function diniz_studio_default_breadcrumb_items() {
	$home_label = (string) diniz_studio_global_option( 'breadcrumb_home_label', __( 'Início', 'dv-visual' ) );
	$blog_label = (string) diniz_studio_global_option( 'breadcrumb_blog_label', __( 'Blog', 'dv-visual' ) );
	$items      = array(
		array( 'label' => $home_label, 'url' => home_url( '/' ) ),
	);
	$posts_page = (int) get_option( 'page_for_posts' );
	$blog_url   = $posts_page ? get_permalink( $posts_page ) : home_url( '/blog/' );

	if ( is_front_page() ) {
		return array();
	}

	if ( is_home() ) {
		$items[] = array( 'label' => $blog_label );
		return $items;
	}

	if ( is_singular( 'post' ) ) {
		$items[]    = array( 'label' => $blog_label, 'url' => $blog_url );
		$categories = get_the_category();
		if ( $categories ) {
			$items[] = array( 'label' => $categories[0]->name, 'url' => get_category_link( $categories[0] ) );
		}
		$items[] = array( 'label' => get_the_title() );
		return $items;
	}

	if ( is_category() || is_tag() || is_date() || is_author() ) {
		$items[] = array( 'label' => $blog_label, 'url' => $blog_url );
		$items[] = array( 'label' => wp_strip_all_tags( get_the_archive_title() ) );
		return $items;
	}

	if ( is_page() ) {
		$page_id   = (int) get_queried_object_id();
		$ancestors = array_reverse( get_post_ancestors( $page_id ) );
		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array( 'label' => get_the_title( $ancestor_id ), 'url' => get_permalink( $ancestor_id ) );
		}
		$items[] = array( 'label' => get_the_title( $page_id ) );
		return $items;
	}

	if ( is_singular() ) {
		$post_type = get_post_type();
		$object    = get_post_type_object( $post_type );
		$archive   = $object && $object->has_archive ? get_post_type_archive_link( $post_type ) : '';
		if ( $object ) {
			$items[] = array( 'label' => $object->labels->name, 'url' => $archive );
		}
		$items[] = array( 'label' => get_the_title() );
		return $items;
	}

	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		$object    = get_post_type_object( $post_type );
		$items[]   = array( 'label' => $object ? $object->labels->name : get_the_archive_title() );
		return $items;
	}

	if ( is_tax() ) {
		$items[] = array( 'label' => wp_strip_all_tags( get_the_archive_title() ) );
		return $items;
	}

	if ( is_search() ) {
		$items[] = array( 'label' => sprintf( __( 'Busca por “%s”', 'dv-visual' ), get_search_query() ) );
		return $items;
	}

	if ( is_404() ) {
		$items[] = array( 'label' => __( 'Página não encontrada', 'dv-visual' ) );
		return $items;
	}

	return $items;
}

/**
 * Render the shared and configurable breadcrumb used by public templates.
 *
 * Appearance, labels, separator and accent are managed in DV Visual >
 * Breadcrumbs. The final item is always marked as the current page.
 *
 * @param array<int,array{label:string,url?:string}> $items      Breadcrumb items.
 * @param string                                    $appearance Either on-dark or on-light.
 * @return string
 */
function diniz_studio_breadcrumbs( $items = array(), $appearance = 'on-dark' ) {
	$enabled = (bool) diniz_studio_global_option( 'breadcrumb_enabled', true );
	if ( ! $enabled ) {
		return '';
	}

	if ( ! $items ) {
		$items = diniz_studio_default_breadcrumb_items();
	}

	$items = array_values(
		array_filter(
			(array) $items,
			static function ( $item ) {
				return is_array( $item ) && ! empty( $item['label'] );
			}
		)
	);

	if ( ! $items ) {
		return '';
	}

	$home_label = (string) diniz_studio_global_option( 'breadcrumb_home_label', __( 'Início', 'dv-visual' ) );
	if ( isset( $items[0] ) && ! empty( $items[0]['url'] ) && untrailingslashit( $items[0]['url'] ) === untrailingslashit( home_url( '/' ) ) ) {
		$items[0]['label'] = $home_label;
	}

	$show_current = (bool) diniz_studio_global_option( 'breadcrumb_show_current', true );
	if ( ! $show_current && count( $items ) > 1 ) {
		array_pop( $items );
	}
	if ( ! $items ) {
		return '';
	}

	$appearance = 'on-light' === $appearance ? 'on-light' : 'on-dark';
	$style      = (string) diniz_studio_global_option( 'breadcrumb_style', 'minimal' );
	$style      = in_array( $style, array( 'minimal', 'glass', 'solid' ), true ) ? $style : 'minimal';
	$separator  = (string) diniz_studio_global_option( 'breadcrumb_separator', '›' );
	$separator  = wp_html_excerpt( wp_strip_all_tags( $separator ), 3, '' ) ?: '›';
	$accent     = sanitize_hex_color( (string) diniz_studio_global_option( 'breadcrumb_accent_color', '#14B8B5' ) );
	$background = sanitize_hex_color( (string) diniz_studio_global_option( 'breadcrumb_background_color', '#FFFFFF' ) );
	$classes    = array( 'dv-breadcrumbs', 'dv-breadcrumbs--' . $appearance, 'dv-breadcrumbs--' . $style );
	$styles     = array();
	if ( $accent ) {
		$styles[] = '--dv-breadcrumb-accent:' . $accent;
	}
	if ( $background ) {
		$styles[] = '--dv-breadcrumb-background:' . $background;
	}

	ob_start();
	?>
	<nav class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"<?php echo $styles ? ' style="' . esc_attr( implode( ';', $styles ) ) . '"' : ''; ?> aria-label="<?php esc_attr_e( 'Navegação estrutural', 'dv-visual' ); ?>" itemscope itemtype="https://schema.org/BreadcrumbList">
		<ol>
			<?php foreach ( $items as $index => $item ) : ?>
				<?php $is_current = $index === count( $items ) - 1; ?>
				<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<?php if ( $index > 0 ) : ?><span class="dv-breadcrumbs__separator" aria-hidden="true"><?php echo esc_html( $separator ); ?></span><?php endif; ?>
					<?php if ( ! $is_current && ! empty( $item['url'] ) ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>" itemprop="item"><span itemprop="name"><?php echo esc_html( $item['label'] ); ?></span></a>
					<?php else : ?>
						<span aria-current="page" itemprop="name"><?php echo esc_html( $item['label'] ); ?></span>
					<?php endif; ?>
					<meta itemprop="position" content="<?php echo esc_attr( $index + 1 ); ?>">
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
	return ob_get_clean();
}

/**
 * Build a concise fallback description for search engines and sharing cards.
 *
 * An ACF description always wins. When it is empty, the theme derives useful
 * copy from the current post, archive or site instead of shipping an empty
 * description tag.
 *
 * @return string
 */
function diniz_studio_meta_description() {
	$description = '';

	if ( is_singular() ) {
		if ( function_exists( 'get_field' ) ) {
			$description = (string) get_field( 'seo_description' );
		}
		if ( ! $description && has_excerpt() ) {
			$description = get_the_excerpt();
		}
		if ( ! $description ) {
			$description = get_post_field( 'post_content', get_queried_object_id() );
		}
	} elseif ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		$configs   = diniz_studio_content_type_config();
		if ( isset( $configs[ $post_type ]['description'] ) ) {
			$description = $configs[ $post_type ]['description'];
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$description = term_description();
	} elseif ( is_search() ) {
		$description = sprintf(
			/* translators: %s: search term. */
			__( 'Artigos, projetos e soluções relacionados a “%s”.', 'dv-visual' ),
			get_search_query()
		);
	} elseif ( is_home() ) {
		$description = __( 'Conteúdos sobre branding, identidade visual, design, sites e estratégia digital para marcas que querem crescer com clareza.', 'dv-visual' );
	}

	if ( ! $description ) {
		$description = get_bloginfo( 'description' );
	}
	if ( ! $description ) {
		$description = __( 'Estratégia, identidade visual e experiências digitais para construir marcas claras, relevantes e memoráveis.', 'dv-visual' );
	}

	$description = strip_shortcodes( $description );
	$description = html_entity_decode( wp_strip_all_tags( $description ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	$description = trim( preg_replace( '/\s+/', ' ', $description ) );

	return wp_html_excerpt( $description, 156, '…' );
}

/**
 * Resolve the canonical URL for archive and sharing metadata.
 *
 * @return string
 */
function diniz_studio_meta_url() {
	if ( is_singular() ) {
		return (string) get_permalink();
	}
	$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	if ( $paged > 1 ) {
		return get_pagenum_link( $paged );
	}
	if ( is_home() ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		return $posts_page ? (string) get_permalink( $posts_page ) : home_url( '/blog/' );
	}
	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		return (string) get_post_type_archive_link( $post_type );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$term_url = get_term_link( get_queried_object() );
		return is_wp_error( $term_url ) ? home_url( '/' ) : (string) $term_url;
	}
	if ( is_search() ) {
		return get_search_link();
	}
	return home_url( '/' );
}

/**
 * Output SEO, social sharing and lightweight structured data.
 *
 * Popular SEO plugins take ownership automatically to avoid duplicate tags.
 *
 * @return void
 */
function diniz_studio_document_meta() {
	echo '<meta name="theme-color" content="#0A2540">' . "\n";

	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) ) {
		return;
	}

	$description = diniz_studio_meta_description();
	$title       = wp_get_document_title();
	$url         = diniz_studio_meta_url();
	$social      = is_singular() && function_exists( 'get_field' ) ? get_field( 'og_image' ) : '';
	$social_url  = is_numeric( $social ) ? wp_get_attachment_image_url( (int) $social, 'full' ) : ( is_array( $social ) && ! empty( $social['url'] ) ? $social['url'] : '' );

	if ( ! $social_url && is_singular() && has_post_thumbnail() ) {
		$social_url = get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
	}

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( str_replace( '-', '_', get_bloginfo( 'language' ) ) ) );
	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( is_singular( 'post' ) ? 'article' : 'website' ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	echo '<meta name="twitter:card" content="' . esc_attr( $social_url ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );

	if ( $social_url ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $social_url ) );
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $social_url ) );
	}
	if ( ! is_singular() && ! is_404() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}

	$schema_type = 'WebPage';
	if ( is_singular( 'post' ) ) {
		$schema_type = 'Article';
	} elseif ( is_singular( 'service' ) ) {
		$schema_type = 'Service';
	} elseif ( is_singular() && function_exists( 'get_field' ) ) {
		$selected_schema = get_field( 'schema_type' );
		if ( in_array( $selected_schema, array( 'WebPage', 'Article', 'Service', 'Product', 'ContactPage', 'FAQPage' ), true ) ) {
			$schema_type = $selected_schema;
		}
	}

	$organization = array(
		'@type' => 'Organization',
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);
	$schema       = array(
		'@context'    => 'https://schema.org',
		'@type'       => $schema_type,
		'name'        => $title,
		'description' => $description,
		'url'         => $url,
		'inLanguage'  => get_bloginfo( 'language' ),
	);

	if ( 'Article' === $schema_type && is_singular() ) {
		$schema['headline']      = get_the_title();
		$schema['datePublished'] = get_the_date( DATE_W3C );
		$schema['dateModified']  = get_the_modified_date( DATE_W3C );
		$schema['author']        = array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', get_queried_object_id() ) ),
		);
		$schema['publisher']     = $organization;
	} elseif ( 'Service' === $schema_type ) {
		$schema['serviceType'] = is_singular() ? get_the_title() : $title;
		$schema['provider']    = $organization;
	} elseif ( 'Product' === $schema_type ) {
		$schema['brand'] = $organization;
	} elseif ( 'FAQPage' === $schema_type && function_exists( 'get_field' ) ) {
		$faq_items = get_field( 'article_faq' );
		if ( is_array( $faq_items ) && $faq_items ) {
			$schema['mainEntity'] = array();
			foreach ( $faq_items as $faq_item ) {
				if ( empty( $faq_item['question'] ) || empty( $faq_item['answer'] ) ) {
					continue;
				}
				$schema['mainEntity'][] = array(
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $faq_item['question'] ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $faq_item['answer'] ),
					),
				);
			}
		}
	}
	if ( $social_url ) {
		$schema['image'] = $social_url;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT )
	);
}
add_action( 'wp_head', 'diniz_studio_document_meta', 2 );

/**
 * Keep internal search results and error pages out of the search index.
 *
 * @param array<string,bool> $robots WordPress robots directives.
 * @return array<string,bool>
 */
function diniz_studio_robots_directives( $robots ) {
	if ( is_search() || is_404() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'diniz_studio_robots_directives' );

function diniz_studio_document_title( $title ) {
	if ( is_singular() && function_exists( 'get_field' ) ) {
		$custom_title = get_field( 'seo_title' );
		if ( $custom_title ) {
			return $custom_title;
		}
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'diniz_studio_document_title' );

function diniz_studio_field_shortcode( $attributes ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$attributes = shortcode_atts(
		array(
			'name'   => '',
			'source' => '',
		),
		$attributes,
		'dv_field'
	);

	if ( ! $attributes['name'] ) {
		return '';
	}

	$value = get_field( sanitize_key( $attributes['name'] ), $attributes['source'] ?: false );

	if ( is_array( $value ) && isset( $value['url'], $value['title'] ) ) {
		return sprintf(
			'<a href="%s" target="%s">%s</a>',
			esc_url( $value['url'] ),
			esc_attr( $value['target'] ?: '_self' ),
			esc_html( $value['title'] )
		);
	}

	if ( is_array( $value ) && isset( $value['url'], $value['alt'] ) ) {
		return sprintf( '<img src="%s" alt="%s">', esc_url( $value['url'] ), esc_attr( $value['alt'] ) );
	}

	return wp_kses_post( (string) $value );
}
add_shortcode( 'dv_field', 'diniz_studio_field_shortcode' );

function diniz_studio_year_shortcode() {
	return esc_html( wp_date( 'Y' ) );
}
add_shortcode( 'dv_year', 'diniz_studio_year_shortcode' );

/**
 * Render the icon selected in the Solução custom post.
 *
 * The ACF image field returns an attachment ID. Reading the raw post meta as a
 * fallback keeps the icon working while ACF is temporarily disabled.
 *
 * @param int    $post_id Service post ID.
 * @param string $context Optional visual context.
 * @return string
 */
function diniz_studio_service_icon( $post_id, $context = 'card' ) {
	$icon = function_exists( 'get_field' ) ? get_field( 'dv_service_icon', $post_id ) : get_post_meta( $post_id, 'dv_service_icon', true );

	if ( is_array( $icon ) && ! empty( $icon['ID'] ) ) {
		$icon = $icon['ID'];
	} elseif ( is_array( $icon ) && ! empty( $icon['id'] ) ) {
		$icon = $icon['id'];
	}

	$classes = 'dv-service-icon dv-service-icon--' . sanitize_html_class( $context );
	$image   = is_numeric( $icon )
		? wp_get_attachment_image(
			(int) $icon,
			'thumbnail',
			false,
			array(
				'class'    => 'dv-service-icon__image',
				'alt'      => '',
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		)
		: '';

	if ( ! $image && is_string( $icon ) && filter_var( $icon, FILTER_VALIDATE_URL ) ) {
		$image = sprintf( '<img class="dv-service-icon__image" src="%s" alt="" loading="lazy" decoding="async">', esc_url( $icon ) );
	}

	if ( $image ) {
		return '<span class="' . esc_attr( $classes ) . '" aria-hidden="true">' . $image . '</span>';
	}

	return '<span class="' . esc_attr( $classes . ' dv-service-icon--empty' ) . '" aria-hidden="true"><i></i></span>';
}

function diniz_studio_services_grid_shortcode() {
	$services = new WP_Query(
		array(
			'post_type'           => 'service',
			'post_status'         => 'publish',
			'posts_per_page'      => 4,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'ignore_sticky_posts' => true,
		)
	);

	if ( ! $services->have_posts() ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return sprintf(
				'<p class="dv-services-empty">Publique conteúdos em <a href="%s">Serviços</a> para preencher esta seção.</p>',
				esc_url( admin_url( 'edit.php?post_type=service' ) )
			);
		}
		return '';
	}

	$service_count = max( 1, min( 4, (int) $services->post_count ) );

	ob_start();
	?>
	<section class="dv-services-dynamic dv-services-dynamic--count-<?php echo esc_attr( $service_count ); ?>" aria-label="<?php esc_attr_e( 'Serviços', 'dv-visual' ); ?>">
		<div class="dv-services-swiper swiper" data-dv-autoplay="true" data-dv-delay="4200">
			<div class="swiper-wrapper dv-services-grid">
				<?php
				while ( $services->have_posts() ) :
					$services->the_post();
					$summary = function_exists( 'get_field' ) ? get_field( 'content_summary' ) : '';
					$summary = $summary ?: get_the_excerpt();
					?>
					<article class="swiper-slide dv-glass-card dv-card dv-service-card">
						<?php echo diniz_studio_service_icon( get_the_ID(), 'card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $summary ), 22 ) ); ?></p>
						<a class="dv-service-link" href="<?php the_permalink(); ?>">
							<?php esc_html_e( 'Ver solução', 'dv-visual' ); ?> <span aria-hidden="true">↗</span>
						</a>
					</article>
				<?php endwhile; ?>
			</div>
			<div class="dv-services-controls dv-swiper-controls">
				<button class="dv-swiper-button dv-swiper-prev" type="button" aria-label="<?php esc_attr_e( 'Solução anterior', 'dv-visual' ); ?>"><span aria-hidden="true">←</span></button>
				<div class="dv-swiper-pagination swiper-pagination"></div>
				<button class="dv-swiper-button dv-swiper-next" type="button" aria-label="<?php esc_attr_e( 'Próxima solução', 'dv-visual' ); ?>"><span aria-hidden="true">→</span></button>
			</div>
		</div>
		<?php if ( $services->found_posts > 4 ) : ?>
			<div class="dv-services-more">
				<a class="wp-element-button" href="<?php echo esc_url( get_post_type_archive_link( 'service' ) ); ?>">
					<?php esc_html_e( 'Ver todos os serviços', 'dv-visual' ); ?> <span aria-hidden="true">↗</span>
				</a>
			</div>
		<?php endif; ?>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_services_grid', 'diniz_studio_services_grid_shortcode' );

/**
 * Render every published Software with a featured image as a four-column
 * Swiper carousel. The front end intentionally displays the logo only.
 *
 * @return string
 */
function diniz_studio_software_carousel_shortcode() {
	$softwares = new WP_Query(
		array(
			'post_type'           => 'software',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'meta_query'          => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			'orderby'             => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'ignore_sticky_posts' => true,
		)
	);

	if ( ! $softwares->have_posts() ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return sprintf(
				'<p class="dv-softwares-empty">Cadastre e publique itens em <a href="%s">Softwares</a> para preencher este carrossel.</p>',
				esc_url( admin_url( 'edit.php?post_type=software' ) )
			);
		}
		return '';
	}

	$kicker           = (string) diniz_studio_content_field( 'dv_software_kicker', 'option' );
	$title            = (string) diniz_studio_content_field( 'dv_software_title', 'option' );
	$text             = (string) diniz_studio_content_field( 'dv_software_text', 'option' );
	$autoplay_setting = function_exists( 'get_field' ) ? get_field( 'dv_software_autoplay', 'option' ) : null;
	$autoplay         = null === $autoplay_setting || '' === $autoplay_setting ? true : (bool) $autoplay_setting;
	$delay            = absint( diniz_studio_content_field( 'dv_software_delay', 'option' ) );
	$delay            = max( 1600, min( 12000, $delay ?: 3000 ) );
	$kicker           = $kicker ?: __( 'Tecnologia no processo', 'dv-visual' );
	$title            = $title ?: __( 'Softwares que usamos todos os dias.', 'dv-visual' );
	$text             = $text ?: __( 'Ferramentas profissionais escolhidas para dar precisão, consistência e agilidade a cada entrega.', 'dv-visual' );
	$heading_id       = wp_unique_id( 'dv-software-title-' );

	ob_start();
	?>
	<section class="dv-software-showcase alignwide" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
		<div class="dv-software-shell">
			<header class="dv-software-heading">
				<div>
					<p class="dv-kicker"><?php echo esc_html( $kicker ); ?></p>
					<h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $title ); ?></h2>
				</div>
				<p><?php echo esc_html( $text ); ?></p>
			</header>
			<div class="dv-software-swiper swiper" data-dv-swiper="softwares" data-dv-slides="4" data-dv-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>" data-dv-delay="<?php echo esc_attr( $delay ); ?>">
				<div class="swiper-wrapper">
					<?php
					while ( $softwares->have_posts() ) :
						$softwares->the_post();
						$post_id    = get_the_ID();
						$logo_id    = get_post_thumbnail_id( $post_id );
						$url        = (string) diniz_studio_content_field( 'content_url', $post_id );
						$new_tab    = (bool) diniz_studio_content_field( 'software_new_tab', $post_id );
						$title_text = get_the_title( $post_id );
						$tag        = $url ? 'a' : 'div';
						?>
						<div class="swiper-slide dv-software-slide">
							<<?php echo tag_escape( $tag ); ?> class="dv-software-card"<?php echo $url ? ' href="' . esc_url( $url ) . '" aria-label="' . esc_attr( sprintf( __( 'Conhecer %s', 'dv-visual' ), $title_text ) ) . '"' : ''; ?><?php echo $url && $new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php
								echo wp_get_attachment_image(
									$logo_id,
									'medium_large',
									false,
									array(
										'class'    => 'dv-software-logo__image',
										'alt'      => $title_text,
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</<?php echo tag_escape( $tag ); ?>>
						</div>
					<?php endwhile; ?>
				</div>
				<div class="dv-software-controls dv-swiper-controls">
					<button class="dv-swiper-button dv-swiper-prev" type="button" aria-label="<?php esc_attr_e( 'Software anterior', 'dv-visual' ); ?>"><span aria-hidden="true">←</span></button>
					<div class="dv-swiper-pagination swiper-pagination"></div>
					<button class="dv-swiper-button dv-swiper-next" type="button" aria-label="<?php esc_attr_e( 'Próximo software', 'dv-visual' ); ?>"><span aria-hidden="true">→</span></button>
				</div>
			</div>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_software_carousel', 'diniz_studio_software_carousel_shortcode' );

/**
 * Native dynamic block used by new patterns instead of exposing a shortcode.
 *
 * @return string
 */
function diniz_studio_services_grid_block() {
	return diniz_studio_services_grid_shortcode();
}

function diniz_studio_software_carousel_block() {
	return diniz_studio_software_carousel_shortcode();
}

function diniz_studio_hero_slider_block() {
	return diniz_studio_hero_slider_shortcode();
}

function diniz_studio_cpt_archive_block( $attributes ) {
	return diniz_studio_cpt_archive_shortcode(
		array(
			'post_type' => isset( $attributes['postType'] ) ? $attributes['postType'] : '',
		)
	);
}

function diniz_studio_cpt_single_block() {
	return diniz_studio_cpt_single_shortcode();
}

function diniz_studio_clients_strip_block() {
	return diniz_studio_clients_strip_shortcode();
}

function diniz_studio_service_list_block() {
	return diniz_studio_service_list_shortcode();
}

function diniz_studio_testimonials_block() {
	return diniz_studio_testimonials_shortcode();
}

function diniz_studio_cpt_featured_block( $attributes ) {
	return diniz_studio_cpt_featured_shortcode(
		array(
			'post_type' => isset( $attributes['postType'] ) ? $attributes['postType'] : 'product',
			'limit'     => isset( $attributes['limit'] ) ? $attributes['limit'] : 3,
			'kicker'    => isset( $attributes['kicker'] ) ? $attributes['kicker'] : '',
			'title'     => isset( $attributes['title'] ) ? $attributes['title'] : '',
		)
	);
}

function diniz_studio_content_hub_block() {
	return diniz_studio_content_hub_shortcode();
}

function diniz_studio_portfolio_showcase_block() {
	return diniz_studio_portfolio_showcase_shortcode();
}

/**
 * Resolve a page URL while preserving a useful fallback on fresh installs.
 *
 * @param string $slug Page slug.
 * @return string
 */
function diniz_studio_menu_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . trim( $slug, '/' ) . '/' );
}

/**
 * Detect a supported social network from its saved icon, label, URL or class.
 *
 * @param string $value Raw social network information.
 * @return string
 */
function diniz_studio_social_icon_slug( $value ) {
	$value = strtolower( remove_accents( wp_strip_all_tags( (string) $value ) ) );
	$icons = array(
		'instagram' => array( 'instagram', 'insta' ),
		'linkedin'  => array( 'linkedin', 'linked-in' ),
		'facebook'  => array( 'facebook', 'fb.com' ),
		'whatsapp'  => array( 'whatsapp', 'wa.me' ),
		'youtube'   => array( 'youtube', 'youtu.be' ),
		'x'         => array( 'x.com', 'twitter', 'rede x' ),
	);

	foreach ( $icons as $icon => $aliases ) {
		foreach ( $aliases as $alias ) {
			if ( false !== strpos( $value, $alias ) ) {
				return $icon;
			}
		}
	}

	return '';
}

/**
 * Render the WordPress Dashicon matching a social network selection.
 *
 * @param string $icon  Selected social icon.
 * @param string $label Accessible social network label.
 * @return string
 */
function diniz_studio_social_icon_markup( $icon, $label = '' ) {
	$icon  = diniz_studio_social_icon_slug( $icon );
	$label = $label ?: ucfirst( $icon ?: __( 'Rede social', 'dv-visual' ) );
	$map   = array(
		'instagram' => 'dashicons-instagram',
		'linkedin'  => 'dashicons-linkedin',
		'facebook'  => 'dashicons-facebook-alt',
		'whatsapp'  => 'dashicons-whatsapp',
		'youtube'   => 'dashicons-youtube',
	);

	if ( 'x' === $icon ) {
		return '<span class="dv-social-icon dv-social-icon--x" aria-hidden="true">X</span><span class="screen-reader-text">' . esc_html( $label ) . '</span>';
	}

	$dashicon = isset( $map[ $icon ] ) ? $map[ $icon ] : 'dashicons-admin-links';
	return '<span class="dashicons ' . esc_attr( $dashicon ) . '" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html( $label ) . '</span>';
}

/**
 * Add the selected icon and accessible label to WordPress social menus.
 *
 * @param array   $atts Menu link attributes.
 * @param WP_Post $item Menu item.
 * @param object  $args Menu arguments.
 * @return array
 */
function diniz_studio_social_menu_link_attributes( $atts, $item, $args ) {
	if ( empty( $args->theme_location ) || 'social' !== $args->theme_location ) {
		return $atts;
	}

	$source                 = implode( ' ', array_merge( array( $item->title, $item->url ), (array) $item->classes ) );
	$icon                   = diniz_studio_social_icon_slug( $source );
	$atts['aria-label']     = wp_strip_all_tags( $item->title );
	$atts['data-dv-social'] = $icon ?: 'link';

	if ( ! empty( $item->url ) && 0 !== strpos( $item->url, home_url( '/' ) ) ) {
		$atts['target'] = '_blank';
		$atts['rel']    = 'noopener';
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'diniz_studio_social_menu_link_attributes', 20, 3 );

/**
 * Replace social menu text with the matching visual icon.
 *
 * @param string  $title Menu item title.
 * @param WP_Post $item  Menu item.
 * @param object  $args  Menu arguments.
 * @return string
 */
function diniz_studio_social_menu_item_title( $title, $item, $args ) {
	if ( empty( $args->theme_location ) || 'social' !== $args->theme_location ) {
		return $title;
	}

	$source = implode( ' ', array_merge( array( $title, $item->url ), (array) $item->classes ) );
	return diniz_studio_social_icon_markup( $source, wp_strip_all_tags( $title ) );
}
add_filter( 'nav_menu_item_title', 'diniz_studio_social_menu_item_title', 20, 3 );

/**
 * Provide a usable menu until the administrator assigns a WordPress menu.
 *
 * @param string $location   Registered menu location.
 * @param string $menu_class CSS class for the list.
 * @param string $menu_id    List ID.
 * @return string
 */
function diniz_studio_menu_fallback( $location, $menu_class, $menu_id ) {
	$archive = static function ( $post_type, $fallback ) {
		$url = get_post_type_archive_link( $post_type );
		return $url ?: home_url( $fallback );
	};

	if ( 'social' === $location ) {
		$social_links = diniz_studio_content_field( 'social_links', 'option' );
		if ( ! is_array( $social_links ) || ! $social_links ) {
			return '';
		}

		$html = sprintf( '<ul id="%s" class="%s">', esc_attr( $menu_id ), esc_attr( $menu_class ) );
		foreach ( $social_links as $social ) {
			if ( empty( $social['url'] ) ) {
				continue;
			}
			$icon  = diniz_studio_social_icon_slug( isset( $social['icon'] ) ? $social['icon'] : '' );
			$label = ! empty( $social['name'] ) ? $social['name'] : ucfirst( $icon ?: __( 'Rede social', 'dv-visual' ) );
			$html .= sprintf(
				'<li class="dv-social-item dv-social-item--%2$s"><a href="%1$s" target="_blank" rel="noopener" aria-label="%3$s" data-dv-social="%2$s">%4$s</a></li>',
				esc_url( $social['url'] ),
				esc_attr( $icon ?: 'link' ),
				esc_attr( $label ),
				diniz_studio_social_icon_markup( $icon, $label ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
		return $html . '</ul>';
	}

	$blog_page = (int) get_option( 'page_for_posts' );
	$blog_url  = $blog_page ? get_permalink( $blog_page ) : home_url( '/blog/' );
	$items     = array(
		array( 'Início', home_url( '/' ) ),
		array( 'Estúdio', diniz_studio_menu_page_url( 'estudio' ) ),
		array( 'Soluções', $archive( 'service', '/solucao/' ) ),
		array( 'Portfólio', $archive( 'portfolio', '/portfolio/' ) ),
		array( 'Produtos', $archive( 'product', '/produto/' ) ),
		array(
			'Recursos',
			'#',
			array(
				array( 'Ferramentas', $archive( 'tool', '/ferramenta/' ) ),
				array( 'Guias', $archive( 'guide', '/guia/' ) ),
				array( 'Central de Ajuda', $archive( 'help_article', '/ajuda/' ) ),
			),
		),
		array( 'Journal', $blog_url ),
		array( 'Contato', diniz_studio_menu_page_url( 'contato' ) ),
	);

	if ( 'footer' === $location ) {
		$items = array_slice( $items, 1, 4 );
		$items[] = array( 'Journal', $blog_url );
	}

	$html = sprintf( '<ul id="%s" class="%s">', esc_attr( $menu_id ), esc_attr( $menu_class ) );
	foreach ( $items as $item ) {
		$children = isset( $item[2] ) && is_array( $item[2] ) ? $item[2] : array();
		$html    .= '<li' . ( $children ? ' class="menu-item-has-children"' : '' ) . '>';
		$html    .= sprintf( '<a href="%1$s">%2$s</a>', esc_url( $item[1] ), esc_html( $item[0] ) );
		if ( $children ) {
			$html .= '<ul class="sub-menu">';
			foreach ( $children as $child ) {
				$html .= sprintf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $child[1] ), esc_html( $child[0] ) );
			}
			$html .= '</ul>';
		}
		$html .= '</li>';
	}

	return $html . '</ul>';
}

/**
 * Return the assigned WordPress menu or the curated first-install fallback.
 *
 * @param string $location   Menu location.
 * @param string $menu_class List class.
 * @param string $menu_id    List ID.
 * @return string
 */
function diniz_studio_menu_markup( $location, $menu_class, $menu_id ) {
	if ( has_nav_menu( $location ) ) {
		$menu = wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => $menu_class,
				'menu_id'        => $menu_id,
				'depth'          => 4,
				'fallback_cb'    => false,
				'echo'           => false,
			)
		);
		if ( $menu ) {
			return $menu;
		}
	}

	return diniz_studio_menu_fallback( $location, $menu_class, $menu_id );
}

/**
 * Render the FAQ Custom Post Type as a responsive accordion.
 *
 * The post title is the question, the WordPress editor is the answer and Page
 * Attributes > Order controls the sequence. The same renderer feeds the Home,
 * reusable FAQ pattern and [dv_faq] shortcode.
 *
 * @param array<string,mixed> $attributes Optional labels and item limit.
 * @return string
 */
function diniz_studio_faq_block( $attributes = array() ) {
	static $instance = 0;
	$instance++;
	$title_id = 'dv-faq-title-' . $instance;

	$attributes = shortcode_atts(
		array(
			'kicker' => __( 'Dúvidas frequentes', 'dv-visual' ),
			'title'  => __( 'Antes de começar.', 'dv-visual' ),
			'text'   => __( 'Respostas objetivas para as perguntas mais comuns.', 'dv-visual' ),
			'limit'  => -1,
		),
		(array) $attributes,
		'dv_faq'
	);

	$query = new WP_Query(
		array(
			'post_type'      => 'faq',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $attributes['limit'],
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	$schema_items = array();
	foreach ( $query->posts as $faq_post ) {
		$plain_answer = trim( wp_strip_all_tags( strip_shortcodes( $faq_post->post_content ) ) );
		if ( $plain_answer ) {
			$schema_items[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( get_the_title( $faq_post ) ),
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $plain_answer ),
			);
		}
	}

	ob_start();
	?>
	<section class="dv-faq-section alignfull" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
		<div class="dv-faq-section__shell alignwide">
			<header class="dv-faq-section__intro">
				<p class="dv-kicker"><?php echo esc_html( $attributes['kicker'] ); ?></p>
				<h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $attributes['title'] ); ?></h2>
				<p><?php echo esc_html( $attributes['text'] ); ?></p>
			</header>
			<div class="dv-faq-section__items">
				<?php if ( $query->have_posts() ) : ?>
					<?php foreach ( $query->posts as $index => $faq_post ) : ?>
						<details class="dv-faq-item wp-block-details"<?php echo 0 === $index ? ' open' : ''; ?>>
							<summary><?php echo esc_html( get_the_title( $faq_post ) ); ?><span aria-hidden="true"></span></summary>
							<div class="dv-faq-item__answer"><?php echo apply_filters( 'the_content', $faq_post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						</details>
					<?php endforeach; ?>
				<?php elseif ( current_user_can( 'edit_posts' ) ) : ?>
					<p class="dv-dynamic-hint"><?php esc_html_e( 'Cadastre perguntas em Perguntas frequentes no painel WordPress.', 'dv-visual' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $schema_items ) : ?>
			<script type="application/ld+json"><?php echo wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $schema_items ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
		<?php endif; ?>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_faq', 'diniz_studio_faq_block' );

/**
 * Render a menu assigned in Appearance > Menus.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function diniz_studio_menu_block( $attributes ) {
	$allowed_locations = array( 'primary', 'footer', 'social' );
	$location          = isset( $attributes['location'] ) ? sanitize_key( $attributes['location'] ) : 'primary';
	$location          = in_array( $location, $allowed_locations, true ) ? $location : 'primary';

	if ( 'primary' !== $location ) {
		$label  = 'social' === $location ? __( 'Redes sociais', 'dv-visual' ) : __( 'Navegação do rodapé', 'dv-visual' );
		$markup = diniz_studio_menu_markup( $location, 'dv-footer-menu__list', 'dv-' . $location . '-menu' );
		if ( ! $markup ) {
			return '';
		}
		return sprintf(
			'<nav class="dv-footer-menu dv-footer-menu--%1$s" aria-label="%2$s">%3$s</nav>',
			esc_attr( $location ),
			esc_attr( $label ),
			$markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	$menu_markup = diniz_studio_menu_markup( 'primary', 'dv-menu-list', 'dv-primary-menu' );
	$cta_label   = diniz_studio_content_field( 'header_cta_label', 'option' ) ?: __( 'Iniciar projeto', 'dv-visual' );
	$cta_link    = diniz_studio_content_field( 'header_cta_url', 'option' );
	$cta_url     = is_array( $cta_link ) && ! empty( $cta_link['url'] ) ? $cta_link['url'] : diniz_studio_menu_page_url( 'proposta' );
	$cta_target  = is_array( $cta_link ) && ! empty( $cta_link['target'] ) ? $cta_link['target'] : '_self';
	/* The offcanvas has a light glass surface, so it always uses the primary logo. */
	$menu_logo   = diniz_studio_content_field( 'brand_logo_dark', 'option' ) ?: diniz_studio_content_field( 'brand_logo_light', 'option' );
	$logo_markup = $menu_logo ? diniz_studio_acf_image( $menu_logo, 'full', 'dv-menu-offcanvas-logo', 'eager' ) : '';

	ob_start();
	?>
	<nav class="dv-main-menu dv-wordpress-menu" aria-label="<?php esc_attr_e( 'Menu principal', 'dv-visual' ); ?>">
		<button class="dv-menu-toggle" type="button" aria-controls="dv-menu-offcanvas" aria-expanded="false">
			<span class="screen-reader-text"><?php esc_html_e( 'Abrir menu', 'dv-visual' ); ?></span>
			<i></i><i></i><i></i>
		</button>
		<div class="dv-menu-offcanvas offcanvas offcanvas-end" id="dv-menu-offcanvas" tabindex="-1" aria-labelledby="dv-menu-offcanvas-title">
			<div class="offcanvas-header">
				<div class="dv-menu-offcanvas-brand">
					<?php if ( $logo_markup ) : ?>
						<a class="dv-menu-offcanvas-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php echo $logo_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<strong id="dv-menu-offcanvas-title" class="screen-reader-text"><?php bloginfo( 'name' ); ?></strong>
					<?php elseif ( has_custom_logo() ) : ?>
						<?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<strong id="dv-menu-offcanvas-title" class="screen-reader-text"><?php bloginfo( 'name' ); ?></strong>
					<?php else : ?>
						<span><?php esc_html_e( 'Navegação', 'dv-visual' ); ?></span>
						<strong id="dv-menu-offcanvas-title"><?php bloginfo( 'name' ); ?></strong>
					<?php endif; ?>
				</div>
				<button class="dv-menu-close" type="button" aria-label="<?php esc_attr_e( 'Fechar menu', 'dv-visual' ); ?>"><i></i><i></i></button>
			</div>
			<div class="offcanvas-body">
				<?php echo $menu_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="dv-menu-mobile-footer">
					<a href="<?php echo esc_url( $cta_url ); ?>" target="<?php echo esc_attr( $cta_target ); ?>"><?php echo esc_html( $cta_label ); ?> <span aria-hidden="true">↗</span></a>
				</div>
			</div>
		</div>
	</nav>
	<?php
	return ob_get_clean();
}

/**
 * Upgrade previously customized header parts without deleting user changes.
 *
 * @param string $block_content Rendered navigation.
 * @param array  $block         Parsed block.
 * @return string
 */
function diniz_studio_replace_legacy_primary_navigation( $block_content, $block ) {
	$class_name = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( $class_name && false !== strpos( ' ' . $class_name . ' ', ' dv-main-menu ' ) ) {
		return diniz_studio_menu_block( array( 'location' => 'primary' ) );
	}

	return $block_content;
}
add_filter( 'render_block_core/navigation', 'diniz_studio_replace_legacy_primary_navigation', 20, 2 );

/**
 * Upgrade the original static home hero to the configurable carousel.
 *
 * This keeps sites that already saved the version 3.4 template in the Site
 * Editor in sync with the new dynamic block without overwriting other edits.
 *
 * @param string $block_content Rendered group.
 * @param array  $block         Parsed block.
 * @return string
 */
function diniz_studio_replace_legacy_home_hero( $block_content, $block ) {
	static $replaced = false;

	if ( $replaced || ! is_front_page() || false !== strpos( $block_content, 'dv-hero-slider' ) ) {
		return $block_content;
	}

	$class_name = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( ! $class_name || false === strpos( ' ' . $class_name . ' ', ' dv-hero ' ) ) {
		return $block_content;
	}

	$replaced = true;
	return diniz_studio_hero_slider_shortcode();
}
add_filter( 'render_block_core/group', 'diniz_studio_replace_legacy_home_hero', 25, 2 );

/**
 * Upgrade the original Query-loop portfolio section saved in Home sections.
 *
 * @param string $block_content Rendered group.
 * @param array  $block         Parsed block.
 * @return string
 */
function diniz_studio_replace_legacy_home_portfolio( $block_content, $block ) {
	static $replaced = false;

	if ( $replaced || ! is_front_page() || false !== strpos( $block_content, 'dv-portfolio-grid--home' ) ) {
		return $block_content;
	}

	$class_name = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( ! $class_name || false === strpos( ' ' . $class_name . ' ', ' dv-portfolio-section ' ) ) {
		return $block_content;
	}

	$replaced = true;
	return diniz_studio_portfolio_showcase_shortcode();
}
add_filter( 'render_block_core/group', 'diniz_studio_replace_legacy_home_portfolio', 26, 2 );

function diniz_studio_register_dynamic_blocks() {
	wp_register_script(
		'diniz-studio-blocks',
		get_template_directory_uri() . '/assets/js/blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
		DINIZ_STUDIO_VERSION,
		true
	);

	$shared = array(
		'api_version'   => 3,
		'editor_script' => 'diniz-studio-blocks',
		'supports'      => array(
			'html'     => false,
			'multiple' => true,
		),
	);

	register_block_type(
		'dv-visual/services-grid',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_services_grid_block' ) )
	);
	register_block_type(
		'dv-visual/software-carousel',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_software_carousel_block' ) )
	);
	register_block_type(
		'dv-visual/hero-slider',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_hero_slider_block' ) )
	);
	register_block_type(
		'dv-visual/cpt-archive',
		array_merge(
			$shared,
			array(
				'attributes'      => array(
					'postType' => array( 'type' => 'string', 'default' => '' ),
				),
				'render_callback' => 'diniz_studio_cpt_archive_block',
			)
		)
	);
	register_block_type(
		'dv-visual/cpt-single',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_cpt_single_block' ) )
	);
	register_block_type(
		'dv-visual/clients-strip',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_clients_strip_block' ) )
	);
	register_block_type(
		'dv-visual/service-list',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_service_list_block' ) )
	);
	register_block_type(
		'dv-visual/testimonials',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_testimonials_block' ) )
	);
	register_block_type(
		'dv-visual/cpt-featured',
		array_merge(
			$shared,
			array(
				'attributes'      => array(
					'postType' => array( 'type' => 'string', 'default' => 'product' ),
					'limit'    => array( 'type' => 'number', 'default' => 3 ),
					'kicker'   => array( 'type' => 'string', 'default' => '' ),
					'title'    => array( 'type' => 'string', 'default' => '' ),
				),
				'render_callback' => 'diniz_studio_cpt_featured_block',
			)
		)
	);
	register_block_type(
		'dv-visual/content-hub',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_content_hub_block' ) )
	);
	register_block_type(
		'dv-visual/portfolio-showcase',
		array_merge( $shared, array( 'render_callback' => 'diniz_studio_portfolio_showcase_block' ) )
	);
	register_block_type(
		'dv-visual/menu',
		array_merge(
			$shared,
			array(
				'attributes'      => array(
					'location' => array( 'type' => 'string', 'default' => 'primary' ),
				),
				'render_callback' => 'diniz_studio_menu_block',
			)
		)
	);
}
add_action( 'init', 'diniz_studio_register_dynamic_blocks', 20 );

/**
 * Compatibility layer for pages saved with DV shortcodes in older versions.
 *
 * Core normally expands shortcode blocks automatically. This late filter also
 * handles content saved as Paragraph, HTML or Pattern markup by page builders.
 *
 * @param string $block_content Rendered block HTML.
 * @return string
 */
function diniz_studio_expand_legacy_shortcodes( $block_content ) {
	if ( false === strpos( $block_content, '[dv_' ) ) {
		return $block_content;
	}

	$known_shortcodes = array(
		'dv_services_grid',
		'dv_software_carousel',
		'dv_hero_slider',
		'dv_cpt_archive',
		'dv_cpt_single',
		'dv_clients_strip',
		'dv_service_list',
		'dv_testimonials',
		'dv_cpt_featured',
		'dv_content_hub',
		'dv_portfolio_archive',
		'dv_portfolio_showcase',
		'dv_field',
		'dv_year',
	);

	foreach ( $known_shortcodes as $shortcode ) {
		if ( false !== strpos( $block_content, '[' . $shortcode ) && shortcode_exists( $shortcode ) ) {
			return do_shortcode( $block_content );
		}
	}

	return $block_content;
}
add_filter( 'render_block', 'diniz_studio_expand_legacy_shortcodes', 99 );

/**
 * Read an ACF value without making the theme depend on ACF at runtime.
 *
 * @param string $name    Field name.
 * @param int    $post_id Post ID.
 * @return mixed
 */
function diniz_studio_content_field( $name, $post_id = 0 ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	return get_field( $name, $post_id ?: false );
}

/**
 * Return the primary public taxonomy for a content type.
 *
 * @param string $post_type Post type.
 * @return string
 */
function diniz_studio_primary_taxonomy( $post_type ) {
	$preferred = array(
		'portfolio'    => 'project_sector',
		'product'      => 'solution_category',
		'service'      => 'solution_category',
		'tool'         => 'solution_category',
		'guide'        => 'guide_category',
		'help_article' => 'guide_category',
		'job'          => 'job_area',
		'team'         => 'project_skill',
		'client'       => 'project_sector',
	);

	return isset( $preferred[ $post_type ] ) ? $preferred[ $post_type ] : '';
}

/**
 * Normalize ACF image values into front-end markup.
 *
 * @param mixed  $image_value Image field value.
 * @param string $size        WordPress image size.
 * @param string $class_name  CSS class.
 * @param string $loading     Native loading strategy.
 * @return string
 */
function diniz_studio_acf_image( $image_value, $size = 'large', $class_name = '', $loading = 'lazy' ) {
	$attachment_id = 0;
	$image_url     = '';
	$image_alt     = '';

	if ( is_numeric( $image_value ) ) {
		$attachment_id = (int) $image_value;
	} elseif ( is_array( $image_value ) ) {
		$attachment_id = isset( $image_value['ID'] ) ? (int) $image_value['ID'] : ( isset( $image_value['id'] ) ? (int) $image_value['id'] : 0 );
		$image_url     = isset( $image_value['url'] ) ? $image_value['url'] : '';
		$image_alt     = isset( $image_value['alt'] ) ? $image_value['alt'] : '';
	} elseif ( is_string( $image_value ) ) {
		$image_url = $image_value;
	}

	if ( $attachment_id && 'image/svg+xml' === get_post_mime_type( $attachment_id ) ) {
		$image_url = wp_get_attachment_url( $attachment_id );
		$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	}

	if ( $attachment_id ) {
		$image_markup = wp_get_attachment_image(
			$attachment_id,
			$size,
			false,
			array(
				'class'   => $class_name,
				'loading' => $loading,
			)
		);
		if ( $image_markup ) {
			return $image_markup;
		}
	}

	if ( $image_url ) {
		return sprintf(
			'<img class="%1$s" src="%2$s" alt="%3$s" loading="%4$s">',
			esc_attr( $class_name ),
			esc_url( $image_url ),
			esc_attr( $image_alt ),
			esc_attr( $loading )
		);
	}

	return '';
}

/**
 * Return a concise description for a card or hero.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function diniz_studio_content_summary( $post_id ) {
	$summary = diniz_studio_content_field( 'content_summary', $post_id );
	$summary = $summary ?: get_the_excerpt( $post_id );

	if ( ! $summary ) {
		$post    = get_post( $post_id );
		$summary = $post ? $post->post_content : '';
	}

	return wp_trim_words( wp_strip_all_tags( (string) $summary ), 26 );
}

/**
 * Render taxonomy chips assigned to a post.
 *
 * @param int    $post_id   Post ID.
 * @param string $taxonomy  Taxonomy slug.
 * @param int    $max_terms Maximum terms.
 * @return string
 */
function diniz_studio_term_chips( $post_id, $taxonomy, $max_terms = 3 ) {
	if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return '';
	}

	$terms = wp_get_post_terms( $post_id, $taxonomy );
	if ( is_wp_error( $terms ) || ! $terms ) {
		return '';
	}

	$terms = array_slice( $terms, 0, $max_terms );
	$html  = '<div class="dv-cpt-terms">';
	foreach ( $terms as $term ) {
		$html .= sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name )
		);
	}
	$html .= '</div>';

	return $html;
}

/**
 * Render the large glass card used by the Soluções page and archive.
 *
 * @param int $post_id  Service post ID.
 * @param int $position Position in a progressively revealed grid. Zero disables
 *                      the load-more attributes.
 * @return string
 */
function diniz_studio_solution_page_card( $post_id, $position = 0 ) {
	$permalink = get_permalink( $post_id );
	$summary   = diniz_studio_content_summary( $post_id );
	$is_hidden = $position > 6;

	ob_start();
	?>
	<article
		class="dv-solution-page-card"
		<?php if ( $position ) : ?>data-dv-service-load-item<?php endif; ?>
		<?php if ( $is_hidden ) : ?>hidden<?php endif; ?>
	>
		<a class="dv-solution-page-card__icon" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo diniz_studio_service_icon( $post_id, 'solution-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<div class="dv-solution-page-card__content">
			<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
			<?php if ( $summary ) : ?>
				<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $summary ), 34 ) ); ?></p>
			<?php endif; ?>
		</div>
		<a class="dv-solution-page-card__link" href="<?php echo esc_url( $permalink ); ?>">
			<?php esc_html_e( 'Ver solução', 'dv-visual' ); ?> <span aria-hidden="true">↗</span>
		</a>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Render the progressive pagination used by solution grids.
 *
 * Six cards are visible on first render. Each activation reveals the next six
 * without a page reload, preserving filters and the user's reading position.
 *
 * @param string $grid_id Grid element ID.
 * @param int    $total   Total number of solution cards.
 * @return string
 */
function diniz_studio_solution_load_more( $grid_id, $total ) {
	if ( $total <= 6 ) {
		return '';
	}

	ob_start();
	?>
	<div class="dv-service-load-more">
		<button
			type="button"
			class="dv-service-load-more__button"
			data-dv-service-load-more
			data-batch-size="6"
			aria-controls="<?php echo esc_attr( $grid_id ); ?>"
		>
			<span><?php esc_html_e( 'Ver mais soluções', 'dv-visual' ); ?></span>
			<i aria-hidden="true">↓</i>
		</button>
		<p class="screen-reader-text" data-dv-service-load-status aria-live="polite"></p>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Complete solution grid for the dedicated Soluções page.
 *
 * @return string
 */
function diniz_studio_solutions_page_grid() {
	$services = new WP_Query(
		array(
			'post_type'           => 'service',
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	$grid_id = wp_unique_id( 'dv-solutions-grid-' );

	ob_start();
	?>
	<section class="dv-solutions-page alignfull" aria-labelledby="dv-solutions-page-title">
		<div class="dv-cpt-shell">
			<header class="dv-solutions-page__heading">
				<p class="dv-kicker dv-kicker-dark"><?php esc_html_e( 'Uma visão, muitas entregas', 'dv-visual' ); ?></p>
				<h2 id="dv-solutions-page-title"><?php esc_html_e( 'Tudo conectado para sua marca ser clara, relevante e desejada.', 'dv-visual' ); ?></h2>
			</header>
			<?php if ( $services->have_posts() ) : ?>
				<div class="dv-solutions-page__grid" id="<?php echo esc_attr( $grid_id ); ?>">
					<?php
					$position = 0;
					while ( $services->have_posts() ) :
						$services->the_post();
						$position++;
						echo diniz_studio_solution_page_card( get_the_ID(), $position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endwhile;
					?>
				</div>
				<?php echo diniz_studio_solution_load_more( $grid_id, $services->post_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php elseif ( current_user_can( 'edit_posts' ) ) : ?>
				<p class="dv-dynamic-hint"><?php esc_html_e( 'Cadastre soluções para preencher esta página.', 'dv-visual' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * Return the terms used to present and filter a portfolio project.
 *
 * Segments are the primary portfolio taxonomy. Categories of solution remain a
 * useful fallback for projects created before Segments were introduced.
 *
 * @param int $post_id Portfolio post ID.
 * @return array<int,WP_Term>
 */
function diniz_studio_portfolio_terms( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'project_sector' );

	if ( is_wp_error( $terms ) || ! $terms ) {
		$terms = wp_get_post_terms( $post_id, 'solution_category' );
	}

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Render the visual portfolio card used on the archive and the Home.
 *
 * @param int    $post_id Portfolio post ID.
 * @param string $context Visual context: archive or home.
 * @return string
 */
function diniz_studio_portfolio_card( $post_id, $context = 'archive' ) {
	$permalink    = get_permalink( $post_id );
	$terms        = diniz_studio_portfolio_terms( $post_id );
	$sector       = diniz_studio_content_field( 'project_card_badge', $post_id );
	$sector       = $sector ?: ( $terms ? $terms[0]->name : diniz_studio_content_field( 'content_badge', $post_id ) );
	$sector       = $sector ?: __( 'Projeto selecionado', 'dv-visual' );
	$sector_slugs = array();

	foreach ( $terms as $term ) {
		$sector_slugs[] = sanitize_title( $term->slug );
	}

	$scope = diniz_studio_content_field( 'project_card_description', $post_id );
	$scope = $scope ?: diniz_studio_content_field( 'project_scope', $post_id );
	if ( ! $scope ) {
		$solution_terms = wp_get_post_terms( $post_id, 'solution_category', array( 'fields' => 'names' ) );
		$scope          = ! is_wp_error( $solution_terms ) && $solution_terms ? implode( ' · ', array_slice( $solution_terms, 0, 2 ) ) : '';
	}
	$scope = $scope ?: __( 'Estratégia, design e experiência digital', 'dv-visual' );
	$card_classes = array(
		'dv-portfolio-card',
		'dv-portfolio-card--' . sanitize_html_class( $context ),
	);
	if ( 'home' === $context ) {
		$card_classes[] = 'swiper-slide';
	}

	$image = get_the_post_thumbnail(
		$post_id,
		'large',
		array(
			'class'    => 'dv-portfolio-card__image',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);

	ob_start();
	?>
	<article
		class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>"
		data-dv-portfolio-card
		data-dv-sectors="<?php echo esc_attr( implode( ' ', array_unique( $sector_slugs ) ) ); ?>"
	>
		<a class="dv-portfolio-card__link" href="<?php echo esc_url( $permalink ); ?>">
			<span class="dv-portfolio-card__visual">
				<?php if ( $image ) : ?>
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<span class="dv-portfolio-card__placeholder" aria-hidden="true"><i></i><strong>✦</strong></span>
				<?php endif; ?>
				<span class="dv-portfolio-card__view"><?php esc_html_e( 'Ver case', 'dv-visual' ); ?> <i aria-hidden="true">↗</i></span>
			</span>
			<span class="dv-portfolio-card__body">
				<span class="dv-portfolio-card__sector"><?php echo esc_html( $sector ); ?></span>
				<strong class="dv-portfolio-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></strong>
				<span class="dv-portfolio-card__scope"><?php echo esc_html( $scope ); ?></span>
			</span>
		</a>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Resolve the editable page that supplies the portfolio hero copy.
 *
 * @return int
 */
function diniz_studio_portfolio_page_id() {
	if ( is_page() && get_queried_object_id() ) {
		return (int) get_queried_object_id();
	}

	$page = get_page_by_path( 'portfolio', OBJECT, 'page' );
	return $page ? (int) $page->ID : 0;
}

/**
 * Build the shared Portfolio query used by the archive and AJAX pagination.
 *
 * @param int    $page            Page to load.
 * @param string $selected_sector Optional project_sector slug.
 * @return array
 */
function diniz_studio_portfolio_query_args( $page = 1, $selected_sector = '' ) {
	$query_args = array(
		'post_type'           => 'portfolio',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'paged'               => max( 1, (int) $page ),
		'orderby'             => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
		'ignore_sticky_posts' => true,
	);

	if ( $selected_sector ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'project_sector',
				'field'    => 'slug',
				'terms'    => sanitize_title( $selected_sector ),
			),
		);
	}

	return $query_args;
}

/**
 * Portfolio landing page with compact breadcrumb, category filter and AJAX load more.
 *
 * Every card comes from the Portfólio CPT. The project_sector taxonomy powers
 * the server-side filter and the page ACF group controls the final CTA.
 *
 * @return string
 */
function diniz_studio_portfolio_archive_shortcode() {
	$page_id         = diniz_studio_portfolio_page_id();
	$filter_action   = $page_id ? get_permalink( $page_id ) : get_post_type_archive_link( 'portfolio' );
	$filter_action   = $filter_action ?: home_url( '/portfolio/' );
	$selected_sector = isset( $_GET['project_category'] ) ? sanitize_title( wp_unslash( $_GET['project_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current_page    = isset( $_GET['portfolio_page'] ) ? max( 1, absint( wp_unslash( $_GET['portfolio_page'] ) ) ) : max( 1, (int) get_query_var( 'paged' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$query        = new WP_Query( diniz_studio_portfolio_query_args( $current_page, $selected_sector ) );
	$projects     = $query->posts;
	$sector_terms = get_terms(
		array(
			'taxonomy'   => 'project_sector',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	$sector_terms = is_wp_error( $sector_terms ) ? array() : $sector_terms;
	$primary_cta  = $page_id ? diniz_studio_content_field( 'hero_primary_cta', $page_id ) : array();

	if ( ! is_array( $primary_cta ) || empty( $primary_cta['url'] ) ) {
		$primary_cta = array(
			'url'    => diniz_studio_menu_page_url( 'proposta' ),
			'title'  => __( 'Iniciar um projeto', 'dv-visual' ),
			'target' => '_self',
		);
	}

	ob_start();
	?>
	<main class="dv-portfolio-archive" data-dv-portfolio>
		<section class="dv-portfolio-hero dv-portfolio-hero--compact">
			<div class="dv-portfolio-shell">
				<?php
				echo diniz_studio_breadcrumbs(
					array(
						array( 'label' => __( 'Início', 'dv-visual' ), 'url' => home_url( '/' ) ),
						array( 'label' => __( 'Portfólio', 'dv-visual' ) ),
					),
					'on-dark'
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</section>

		<section class="dv-portfolio-library" aria-labelledby="dv-portfolio-library-title">
			<div class="dv-portfolio-shell">
				<header class="dv-portfolio-library__heading">
					<div><p class="dv-kicker"><?php esc_html_e( 'Explore os projetos', 'dv-visual' ); ?></p><h1 id="dv-portfolio-library-title"><?php esc_html_e( 'Cada desafio pede uma resposta única.', 'dv-visual' ); ?></h1></div>
				</header>

				<?php if ( $sector_terms ) : ?>
					<form class="dv-portfolio-filter-form" action="<?php echo esc_url( $filter_action ); ?>" method="get">
						<div class="dv-portfolio-filter-form__field">
							<label for="dv-project-category"><?php esc_html_e( 'Filtrar por categoria', 'dv-visual' ); ?></label>
							<select id="dv-project-category" name="project_category">
								<option value=""><?php esc_html_e( 'Todas as categorias', 'dv-visual' ); ?></option>
								<?php foreach ( $sector_terms as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_sector, $term->slug ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="dv-portfolio-filter-form__actions">
							<button class="dv-portfolio-button dv-portfolio-button--filter" type="submit"><?php esc_html_e( 'Filtrar projetos', 'dv-visual' ); ?></button>
							<?php if ( $selected_sector ) : ?><a href="<?php echo esc_url( $filter_action ); ?>"><?php esc_html_e( 'Limpar filtro', 'dv-visual' ); ?></a><?php endif; ?>
						</div>
					</form>
				<?php endif; ?>

				<?php if ( $projects ) : ?>
					<div class="dv-portfolio-grid" id="dv-portfolio-results">
						<?php foreach ( $projects as $project ) : ?>
							<?php echo diniz_studio_portfolio_card( $project->ID, 'archive' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="dv-cpt-empty dv-glass-card">
						<span aria-hidden="true">○</span>
						<h2><?php esc_html_e( 'Nenhum projeto encontrado.', 'dv-visual' ); ?></h2>
						<p><?php esc_html_e( 'Escolha outra categoria ou limpe o filtro para visualizar todos os cases.', 'dv-visual' ); ?></p>
						<a class="wp-element-button" href="<?php echo esc_url( $filter_action ); ?>"><?php esc_html_e( 'Ver todos os projetos', 'dv-visual' ); ?></a>
						<?php if ( current_user_can( 'edit_posts' ) ) : ?><a class="wp-element-button is-style-outline" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=portfolio' ) ); ?>"><?php esc_html_e( 'Adicionar projeto', 'dv-visual' ); ?></a><?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $query->max_num_pages > $current_page ) : ?>
					<?php
					$next_page_url = add_query_arg( 'portfolio_page', $current_page + 1, $filter_action );
					if ( $selected_sector ) {
						$next_page_url = add_query_arg( 'project_category', $selected_sector, $next_page_url );
					}
					?>
					<div class="dv-portfolio-load-more">
						<a
							class="dv-portfolio-load-more__button dv-portfolio-button"
							href="<?php echo esc_url( $next_page_url ); ?>"
							data-dv-portfolio-load-more
							data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'diniz_studio_portfolio_load_more' ) ); ?>"
							data-page="<?php echo esc_attr( $current_page + 1 ); ?>"
							data-max-pages="<?php echo esc_attr( (int) $query->max_num_pages ); ?>"
							data-sector="<?php echo esc_attr( $selected_sector ); ?>"
							aria-controls="dv-portfolio-results"
						>
							<span data-dv-load-more-label><?php esc_html_e( 'Ver mais projetos', 'dv-visual' ); ?></span>
							<i aria-hidden="true">↓</i>
						</a>
						<p class="dv-portfolio-load-more__status" data-dv-load-more-status aria-live="polite"></p>
					</div>
				<?php endif; ?>

				<aside class="dv-portfolio-cta">
					<div><span><?php esc_html_e( 'Seu projeto pode estar aqui', 'dv-visual' ); ?></span><h2><?php esc_html_e( 'Vamos construir o seu próximo case.', 'dv-visual' ); ?></h2><p><?php esc_html_e( 'Uma conversa estratégica para entender o momento da sua marca e desenhar o próximo passo.', 'dv-visual' ); ?></p></div>
					<a class="dv-portfolio-button dv-portfolio-button--primary" href="<?php echo esc_url( $primary_cta['url'] ); ?>" target="<?php echo esc_attr( $primary_cta['target'] ?: '_self' ); ?>"><?php echo esc_html( $primary_cta['title'] ); ?> <span aria-hidden="true">↗</span></a>
				</aside>
			</div>
		</section>
	</main>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_portfolio_archive', 'diniz_studio_portfolio_archive_shortcode' );

/**
 * Return the next Portfolio page to the progressive "Ver mais" control.
 */
function diniz_studio_portfolio_load_more_ajax() {
	check_ajax_referer( 'diniz_studio_portfolio_load_more', 'nonce' );

	$page            = isset( $_POST['page'] ) ? max( 2, absint( wp_unslash( $_POST['page'] ) ) ) : 2;
	$selected_sector = isset( $_POST['sector'] ) ? sanitize_title( wp_unslash( $_POST['sector'] ) ) : '';
	$query           = new WP_Query( diniz_studio_portfolio_query_args( $page, $selected_sector ) );

	ob_start();
	foreach ( $query->posts as $project ) {
		echo diniz_studio_portfolio_card( $project->ID, 'archive' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	$html         = ob_get_clean();
	$has_more     = $page < (int) $query->max_num_pages;
	$message      = __( 'Novos projetos exibidos.', 'dv-visual' );

	wp_reset_postdata();
	wp_send_json_success(
		array(
			'html'         => $html,
			'loaded_count' => $loaded_count,
			'next_page'    => $page + 1,
			'has_more'     => $has_more,
			'message'      => $has_more ? $message : $message . ' ' . __( 'Todos os projetos foram carregados.', 'dv-visual' ),
		)
	);
}
add_action( 'wp_ajax_diniz_studio_portfolio_load_more', 'diniz_studio_portfolio_load_more_ajax' );
add_action( 'wp_ajax_nopriv_diniz_studio_portfolio_load_more', 'diniz_studio_portfolio_load_more_ajax' );

/**
 * Render the Home portfolio showcase with the same visual language as archive.
 *
 * @return string
 */
function diniz_studio_portfolio_showcase_shortcode() {
	$query = new WP_Query(
		array(
			'post_type'           => 'portfolio',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'ignore_sticky_posts' => true,
		)
	);
	$archive_url = get_post_type_archive_link( 'portfolio' ) ?: home_url( '/portfolio/' );
	$project_count = max( 1, min( 6, (int) $query->post_count ) );

	ob_start();
	?>
	<section class="dv-portfolio-section dv-portfolio-home alignfull" aria-labelledby="dv-portfolio-home-title">
		<div class="dv-portfolio-shell">
			<header class="dv-portfolio-home__heading">
				<div><p class="dv-kicker"><?php esc_html_e( 'Projetos selecionados', 'dv-visual' ); ?></p><h2 id="dv-portfolio-home-title"><?php esc_html_e( 'Trabalho que fala por si.', 'dv-visual' ); ?></h2></div>
				<a class="dv-portfolio-home__all" href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Ver portfólio completo', 'dv-visual' ); ?> <span aria-hidden="true">↗</span></a>
			</header>
			<?php if ( $query->have_posts() ) : ?>
				<div class="dv-portfolio-home__slider swiper" data-dv-swiper="portfolio-home" aria-label="<?php esc_attr_e( 'Projetos selecionados', 'dv-visual' ); ?>">
					<div class="dv-portfolio-grid dv-portfolio-grid--home dv-portfolio-grid--count-<?php echo esc_attr( $project_count ); ?> swiper-wrapper">
						<?php while ( $query->have_posts() ) : $query->the_post(); ?>
							<?php echo diniz_studio_portfolio_card( get_the_ID(), 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endwhile; ?>
					</div>
					<div class="dv-portfolio-home__controls dv-swiper-controls">
						<button class="dv-swiper-button dv-swiper-prev" type="button" aria-label="<?php esc_attr_e( 'Projeto anterior', 'dv-visual' ); ?>"><span aria-hidden="true">←</span></button>
						<div class="dv-swiper-pagination swiper-pagination"></div>
						<button class="dv-swiper-button dv-swiper-next" type="button" aria-label="<?php esc_attr_e( 'Próximo projeto', 'dv-visual' ); ?>"><span aria-hidden="true">→</span></button>
					</div>
				</div>
			<?php elseif ( current_user_can( 'edit_posts' ) ) : ?>
				<div class="dv-empty-projects"><p><?php esc_html_e( 'Adicione projetos em Portfólio para preencher esta vitrine automaticamente.', 'dv-visual' ); ?></p></div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_portfolio_showcase', 'diniz_studio_portfolio_showcase_shortcode' );

/**
 * Render one reusable Custom Post Type card.
 *
 * @param int $post_id Post ID.
 * @param int $index   Visual index.
 * @return string
 */
function diniz_studio_content_card( $post_id, $index = 1 ) {
	$post_type = get_post_type( $post_id );
	$configs   = diniz_studio_content_type_config();

	if ( ! isset( $configs[ $post_type ] ) ) {
		return '';
	}

	if ( 'service' === $post_type ) {
		return diniz_studio_solution_page_card( $post_id, $index );
	}

	$config       = $configs[ $post_type ];
	$taxonomy     = diniz_studio_primary_taxonomy( $post_type );
	$badge        = diniz_studio_content_field( 'content_badge', $post_id );
	$metric       = diniz_studio_content_field( 'content_metric', $post_id );
	$metric_label = diniz_studio_content_field( 'content_metric_label', $post_id );
	$role         = diniz_studio_content_field( 'person_role', $post_id );
	$company      = diniz_studio_content_field( 'person_company', $post_id );
	$rating       = (int) diniz_studio_content_field( 'rating', $post_id );
	$image        = get_the_post_thumbnail(
		$post_id,
		'large',
		array(
			'class'   => 'dv-cpt-card__image',
			'loading' => 'lazy',
		)
	);

	if ( ! $image && in_array( $post_type, array( 'team', 'testimonial' ), true ) ) {
		$image = diniz_studio_acf_image( diniz_studio_content_field( 'person_avatar', $post_id ), 'medium_large', 'dv-cpt-card__image' );
	}

	if ( ! $image && in_array( $post_type, array( 'client', 'award', 'product', 'tool' ), true ) ) {
		$image = diniz_studio_acf_image( diniz_studio_content_field( 'content_logo', $post_id ), 'medium_large', 'dv-cpt-card__image dv-cpt-card__image--contain' );
	}

	if ( ! $badge && $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy );
		$badge = ! is_wp_error( $terms ) && $terms ? $terms[0]->name : '';
	}

	ob_start();
	?>
	<article class="dv-cpt-card dv-cpt-card--<?php echo esc_attr( $post_type ); ?>">
		<a class="dv-cpt-card__visual" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( $image ) : ?>
				<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<span class="dv-cpt-card__placeholder"><small><?php echo esc_html( $config['singular'] ); ?></small><strong><?php echo esc_html( str_pad( (string) $index, 2, '0', STR_PAD_LEFT ) ); ?></strong></span>
			<?php endif; ?>
		</a>
		<div class="dv-cpt-card__body">
			<div class="dv-cpt-card__topline">
				<span><?php echo esc_html( $badge ?: $config['singular'] ); ?></span>
				<?php if ( $rating ) : ?>
					<span class="dv-cpt-rating" aria-label="<?php echo esc_attr( sprintf( '%d de 5 estrelas', $rating ) ); ?>"><?php echo esc_html( str_repeat( '★', min( 5, $rating ) ) ); ?></span>
				<?php endif; ?>
			</div>
			<h3><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
			<?php if ( $role || $company ) : ?>
				<p class="dv-cpt-card__person"><?php echo esc_html( implode( ' · ', array_filter( array( $role, $company ) ) ) ); ?></p>
			<?php endif; ?>
			<p class="dv-cpt-card__summary"><?php echo esc_html( diniz_studio_content_summary( $post_id ) ); ?></p>
			<?php if ( $metric ) : ?>
				<p class="dv-cpt-card__metric"><strong><?php echo esc_html( $metric ); ?></strong><?php if ( $metric_label ) : ?><small><?php echo esc_html( $metric_label ); ?></small><?php endif; ?></p>
			<?php endif; ?>
			<a class="dv-cpt-card__link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( $config['cta'] ); ?> <span aria-hidden="true">↗</span></a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * Complete archive view shared by all custom content types.
 *
 * @param array $attributes Shortcode attributes.
 * @return string
 */
function diniz_studio_cpt_archive_shortcode( $attributes ) {
	$attributes = shortcode_atts( array( 'post_type' => '' ), $attributes, 'dv_cpt_archive' );
	$post_type  = sanitize_key( $attributes['post_type'] );
	$configs    = diniz_studio_content_type_config();

	if ( ! $post_type && is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
	}

	if ( ! isset( $configs[ $post_type ] ) ) {
		return '';
	}

	if ( 'portfolio' === $post_type ) {
		return diniz_studio_portfolio_archive_shortcode();
	}

	$config       = $configs[ $post_type ];
	$taxonomy     = diniz_studio_primary_taxonomy( $post_type );
	$search       = isset( $_GET['dv_search'] ) ? sanitize_text_field( wp_unslash( $_GET['dv_search'] ) ) : '';
	$filter       = isset( $_GET['dv_filter'] ) ? sanitize_title( wp_unslash( $_GET['dv_filter'] ) ) : '';
	$current_page = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$is_service_archive = 'service' === $post_type;
	$query_args   = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => $is_service_archive ? -1 : 9,
		'paged'               => $is_service_archive ? 1 : $current_page,
		's'                   => $search,
		'orderby'             => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => $is_service_archive,
	);

	if ( $taxonomy && $filter ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $filter,
			),
		);
	}

	$content_query = new WP_Query( $query_args );
	$grid_id       = $is_service_archive ? wp_unique_id( 'dv-solutions-archive-grid-' ) : '';
	$filter_terms  = $taxonomy ? get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	) : array();

	ob_start();
	?>
	<main class="dv-cpt-archive dv-cpt-archive--<?php echo esc_attr( $post_type ); ?>">
		<section class="dv-cpt-hero">
			<div class="dv-cpt-shell">
				<?php echo diniz_studio_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="dv-kicker dv-kicker-dark"><?php echo esc_html( $config['kicker'] ); ?></p>
				<h1><?php echo esc_html( $config['name'] ); ?></h1>
				<p class="dv-cpt-hero__copy"><?php echo esc_html( $config['description'] ); ?></p>
			</div>
		</section>
		<section class="dv-cpt-listing">
			<div class="dv-cpt-shell">
				<form class="dv-cpt-filters dv-glass-card" action="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>" method="get">
					<label>
						<span class="screen-reader-text"><?php esc_html_e( 'Buscar', 'dv-visual' ); ?></span>
						<input type="search" name="dv_search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( sprintf( 'Buscar em %s', strtolower( $config['name'] ) ) ); ?>">
					</label>
					<?php if ( $taxonomy && ! is_wp_error( $filter_terms ) && $filter_terms ) : ?>
						<label>
							<span class="screen-reader-text"><?php esc_html_e( 'Filtrar por categoria', 'dv-visual' ); ?></span>
							<select name="dv_filter">
								<option value=""><?php esc_html_e( 'Todas as categorias', 'dv-visual' ); ?></option>
								<?php foreach ( $filter_terms as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filter, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					<?php endif; ?>
					<button type="submit"><?php esc_html_e( 'Aplicar filtros', 'dv-visual' ); ?></button>
					<?php if ( $search || $filter ) : ?>
						<a href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>"><?php esc_html_e( 'Limpar', 'dv-visual' ); ?></a>
					<?php endif; ?>
				</form>

				<?php if ( $content_query->have_posts() ) : ?>
					<div class="dv-cpt-grid"<?php if ( $grid_id ) : ?> id="<?php echo esc_attr( $grid_id ); ?>"<?php endif; ?>>
						<?php
						$index = 0;
						while ( $content_query->have_posts() ) :
							$content_query->the_post();
							$index++;
							echo diniz_studio_content_card( get_the_ID(), $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						endwhile;
						?>
					</div>
					<?php
					if ( $is_service_archive ) {
						echo diniz_studio_solution_load_more( $grid_id, $content_query->post_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
					<?php
					$pagination = $is_service_archive ? '' : paginate_links(
						array(
							'current'   => $current_page,
							'total'     => $content_query->max_num_pages,
							'type'      => 'list',
							'prev_text' => '← <span>Anterior</span>',
							'next_text' => '<span>Próxima</span> →',
							'add_args'  => array_filter(
								array(
									'dv_search' => $search,
									'dv_filter' => $filter,
								)
							),
						)
					);
					if ( $pagination ) :
						?>
						<nav class="dv-cpt-pagination" aria-label="<?php esc_attr_e( 'Paginação', 'dv-visual' ); ?>"><?php echo wp_kses_post( $pagination ); ?></nav>
					<?php endif; ?>
				<?php else : ?>
					<div class="dv-cpt-empty dv-glass-card">
						<span aria-hidden="true">○</span>
						<h2><?php esc_html_e( 'Nada encontrado por aqui.', 'dv-visual' ); ?></h2>
						<p><?php esc_html_e( 'Tente remover os filtros ou volte em breve para conferir novos conteúdos.', 'dv-visual' ); ?></p>
						<?php if ( current_user_can( 'edit_posts' ) ) : ?>
							<a class="wp-element-button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $post_type ) ); ?>"><?php echo esc_html( sprintf( 'Adicionar %s', strtolower( $config['singular'] ) ) ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</main>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_cpt_archive', 'diniz_studio_cpt_archive_shortcode' );

/**
 * Create key-value facts for a singular page.
 *
 * @param int    $post_id   Post ID.
 * @param string $post_type Post type.
 * @return array<int,array<string,string>>
 */
function diniz_studio_content_facts( $post_id, $post_type ) {
	$facts        = array();
	$metric       = diniz_studio_content_field( 'content_metric', $post_id );
	$metric_label = diniz_studio_content_field( 'content_metric_label', $post_id );
	$role         = diniz_studio_content_field( 'person_role', $post_id );
	$company      = diniz_studio_content_field( 'person_company', $post_id );
	$reading_time = diniz_studio_content_field( 'reading_time', $post_id );

	if ( 'portfolio' === $post_type ) {
		$portfolio_facts = array(
			'Cliente' => diniz_studio_content_field( 'project_client', $post_id ),
			'Ano'     => diniz_studio_content_field( 'project_year', $post_id ),
			'Escopo'  => diniz_studio_content_field( 'project_scope', $post_id ),
		);
		foreach ( $portfolio_facts as $label => $value ) {
			if ( $value ) {
				$facts[] = array( 'label' => $label, 'value' => wp_strip_all_tags( (string) $value ) );
			}
		}
	}

	if ( $role ) {
		$facts[] = array( 'label' => 'Atuação', 'value' => wp_strip_all_tags( (string) $role ) );
	}
	if ( $company ) {
		$facts[] = array( 'label' => 'Empresa', 'value' => wp_strip_all_tags( (string) $company ) );
	}
	if ( $metric ) {
		$facts[] = array( 'label' => $metric_label ?: 'Destaque', 'value' => wp_strip_all_tags( (string) $metric ) );
	}
	if ( $reading_time ) {
		$facts[] = array( 'label' => 'Tempo de leitura', 'value' => sprintf( '%s min', $reading_time ) );
	}
	if ( diniz_studio_content_field( 'show_updated_date', $post_id ) ) {
		$facts[] = array( 'label' => 'Atualizado em', 'value' => get_the_modified_date( 'd/m/Y', $post_id ) );
	}
	$author_override = (int) diniz_studio_content_field( 'author_override', $post_id );
	if ( $author_override ) {
		$author = get_userdata( $author_override );
		if ( $author ) {
			$facts[] = array( 'label' => 'Autoria', 'value' => $author->display_name );
		}
	}

	$taxonomy = diniz_studio_primary_taxonomy( $post_type );
	if ( $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $terms ) && $terms ) {
			$tax_object = get_taxonomy( $taxonomy );
			$facts[]    = array(
				'label' => $tax_object ? $tax_object->labels->singular_name : 'Categoria',
				'value' => implode( ' · ', $terms ),
			);
		}
	}

	return array_slice( $facts, 0, 4 );
}

/**
 * Render ACF gallery values.
 *
 * @param array $gallery Gallery values.
 * @return string
 */
function diniz_studio_content_gallery( $gallery ) {
	if ( ! is_array( $gallery ) || ! $gallery ) {
		return '';
	}

	$html = '<section class="dv-cpt-gallery" aria-label="Galeria"><div class="dv-cpt-gallery__grid">';
	foreach ( $gallery as $image ) {
		$image_html = diniz_studio_acf_image( $image, 'large', 'dv-cpt-gallery__image' );
		if ( $image_html ) {
			$html .= '<figure>' . $image_html . '</figure>';
		}
	}
	$html .= '</div></section>';

	return $html;
}

/**
 * Normalize an ACF file value used by the Portfolio Scroll container.
 *
 * @param mixed $file_value ACF file value as attachment ID, array or URL.
 * @return array
 */
function diniz_studio_portfolio_scroll_media_data( $file_value ) {
	$attachment_id = 0;
	$url           = '';
	$mime_type     = '';
	$title         = '';
	$alt           = '';

	if ( is_numeric( $file_value ) ) {
		$attachment_id = (int) $file_value;
	} elseif ( is_array( $file_value ) ) {
		$attachment_id = isset( $file_value['ID'] ) ? (int) $file_value['ID'] : ( isset( $file_value['id'] ) ? (int) $file_value['id'] : 0 );
		$url           = isset( $file_value['url'] ) ? (string) $file_value['url'] : '';
		$mime_type     = isset( $file_value['mime_type'] ) ? (string) $file_value['mime_type'] : '';
		$title         = isset( $file_value['title'] ) ? (string) $file_value['title'] : '';
		$alt           = isset( $file_value['alt'] ) ? (string) $file_value['alt'] : '';
	} elseif ( is_string( $file_value ) ) {
		$url = $file_value;
	}

	if ( $attachment_id ) {
		$url       = (string) wp_get_attachment_url( $attachment_id );
		$mime_type = (string) get_post_mime_type( $attachment_id );
		$title     = (string) get_the_title( $attachment_id );
		$alt       = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	}

	if ( $url && ! $mime_type ) {
		$file_type = wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$mime_type = ! empty( $file_type['type'] ) ? (string) $file_type['type'] : '';
	}

	if ( ! $title && $url ) {
		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$title = pathinfo( $path, PATHINFO_FILENAME );
	}

	return array(
		'attachment_id' => $attachment_id,
		'url'           => $url,
		'mime_type'     => $mime_type,
		'title'         => $title,
		'alt'           => $alt,
		'is_pdf'        => 'application/pdf' === $mime_type || (bool) preg_match( '/\.pdf(?:$|[?#])/i', $url ),
	);
}

/**
 * Complete editorial case page used only by the Portfolio post type.
 *
 * The main narrative is the native WordPress editor. The project facts,
 * challenge, strategy, solution, results, palette, gallery and video are ACF.
 * The archive button intentionally appears only in the final page section.
 *
 * @return string
 */
function diniz_studio_portfolio_single_shortcode() {
	$post_id = get_queried_object_id();

	if ( ! $post_id || 'portfolio' !== get_post_type( $post_id ) ) {
		return '';
	}

	$archive_url = get_post_type_archive_link( 'portfolio' );
	$archive_url = $archive_url ?: home_url( '/portfolio/' );
	$terms       = diniz_studio_portfolio_terms( $post_id );
	$sector      = $terms ? $terms[0]->name : diniz_studio_content_field( 'content_badge', $post_id );
	$sector      = $sector ?: __( 'Projeto selecionado', 'dv-visual' );
	$summary     = diniz_studio_content_field( 'content_summary', $post_id );
	$summary     = $summary ?: get_the_excerpt( $post_id );
	$client      = diniz_studio_content_field( 'project_client', $post_id );
	$year        = diniz_studio_content_field( 'project_year', $post_id );
	$scope       = diniz_studio_content_field( 'project_scope', $post_id );
	$project_url = diniz_studio_content_field( 'project_url', $post_id );
	$content_raw = (string) get_post_field( 'post_content', $post_id );
	$content     = $content_raw ? apply_filters( 'the_content', $content_raw ) : '';
	$gallery     = diniz_studio_content_field( 'project_gallery', $post_id );
	$gallery     = $gallery ?: diniz_studio_content_field( 'content_gallery', $post_id );
	$gallery_kicker = diniz_studio_content_field( 'project_gallery_kicker', $post_id );
	$gallery_title  = diniz_studio_content_field( 'project_gallery_title', $post_id );
	$gallery_text   = diniz_studio_content_field( 'project_gallery_text', $post_id );
	$gallery_kicker = $gallery_kicker ?: __( 'Galeria do projeto', 'dv-visual' );
	$gallery_title  = $gallery_title ?: __( 'Continue sua jornada de descoberta.', 'dv-visual' );
	$scroll_items   = diniz_studio_content_field( 'project_scroll_items', $post_id );
	$scroll_kicker  = diniz_studio_content_field( 'project_scroll_kicker', $post_id );
	$scroll_title   = diniz_studio_content_field( 'project_scroll_title', $post_id );
	$scroll_text    = diniz_studio_content_field( 'project_scroll_text', $post_id );
	$scroll_kicker  = $scroll_kicker ?: __( 'Apresentação completa', 'dv-visual' );
	$scroll_title   = $scroll_title ?: __( 'Explore o projeto em detalhes.', 'dv-visual' );
	$scroll_slides  = array();

	if ( is_array( $scroll_items ) ) {
		foreach ( $scroll_items as $scroll_item ) {
			$file_value = is_array( $scroll_item ) && isset( $scroll_item['media'] ) ? $scroll_item['media'] : '';
			$media      = diniz_studio_portfolio_scroll_media_data( $file_value );

			if ( empty( $media['url'] ) ) {
				continue;
			}

			$scroll_slides[] = array(
				'file_value' => $file_value,
				'media'      => $media,
				'title'      => is_array( $scroll_item ) && ! empty( $scroll_item['title'] ) ? $scroll_item['title'] : $media['title'],
				'caption'    => is_array( $scroll_item ) && ! empty( $scroll_item['caption'] ) ? $scroll_item['caption'] : '',
			);
		}
	}
	$results     = diniz_studio_content_field( 'project_results', $post_id );
	$palette     = diniz_studio_content_field( 'project_palette', $post_id );
	$video       = diniz_studio_content_field( 'project_video', $post_id );
	$featured    = get_the_post_thumbnail(
		$post_id,
		'full',
		array(
			'class'         => 'dv-portfolio-case__featured-image',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
			'decoding'      => 'async',
		)
	);
	$facts = array_filter(
		array(
			__( 'Cliente', 'dv-visual' ) => $client,
			__( 'Setor', 'dv-visual' )   => $sector,
			__( 'Serviço', 'dv-visual' ) => $scope,
			__( 'Entregue em', 'dv-visual' ) => $year,
		)
	);
	$case_sections = array(
		__( 'O desafio', 'dv-visual' )   => diniz_studio_content_field( 'project_challenge', $post_id ),
		__( 'A estratégia', 'dv-visual' ) => diniz_studio_content_field( 'project_strategy', $post_id ),
		__( 'A solução', 'dv-visual' )    => diniz_studio_content_field( 'project_solution', $post_id ),
	);
	$case_sections = array_filter( $case_sections );

	$related_args = array(
		'post_type'           => 'portfolio',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
	);

	if ( $terms && ! empty( $terms[0]->taxonomy ) ) {
		$related_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => $terms[0]->taxonomy,
				'field'    => 'term_id',
				'terms'    => array( (int) $terms[0]->term_id ),
			),
		);
	}

	$related = new WP_Query( $related_args );

	ob_start();
	?>
	<main class="dv-portfolio-case">
		<!-- ABERTURA: título, ficha ACF e imagem destacada. -->
		<section class="dv-portfolio-case__hero">
			<div class="dv-portfolio-case__shell">
				<?php
				echo diniz_studio_breadcrumbs(
					array(
						array( 'label' => __( 'Início', 'dv-visual' ), 'url' => home_url( '/' ) ),
						array( 'label' => __( 'Portfólio', 'dv-visual' ), 'url' => $archive_url ),
						array( 'label' => get_the_title( $post_id ) ),
					),
					'on-light'
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

				<div class="dv-portfolio-case__hero-grid">
					<div class="dv-portfolio-case__intro">
						<p class="dv-portfolio-case__badge"><i aria-hidden="true"></i><?php echo esc_html( sprintf( 'Case · %s', $sector ) ); ?></p>
						<h1><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
						<?php if ( $summary ) : ?>
							<p class="dv-portfolio-case__summary"><?php echo esc_html( wp_strip_all_tags( (string) $summary ) ); ?></p>
						<?php endif; ?>

						<?php if ( $facts ) : ?>
							<dl class="dv-portfolio-case__facts">
								<?php foreach ( $facts as $label => $value ) : ?>
									<div>
										<dt><?php echo esc_html( $label ); ?></dt>
										<dd><?php echo esc_html( wp_strip_all_tags( (string) $value ) ); ?></dd>
									</div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>

						<?php if ( $project_url ) : ?>
							<a class="dv-portfolio-case__visit" href="<?php echo esc_url( $project_url ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Visitar projeto', 'dv-visual' ); ?> <span aria-hidden="true">↗</span>
							</a>
						<?php endif; ?>
					</div>

					<figure class="dv-portfolio-case__featured">
						<?php if ( $featured ) : ?>
							<?php echo $featured; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<span class="dv-portfolio-case__featured-placeholder" aria-hidden="true"><i></i><strong>✦</strong></span>
						<?php endif; ?>
					</figure>
				</div>
			</div>
		</section>

		<!-- CONTEÚDO LIVRE: tudo o que for criado no editor visual do WordPress. -->
		<?php if ( $content ) : ?>
			<section class="dv-portfolio-case__editor-section">
				<div class="dv-portfolio-case__shell dv-portfolio-case__editor-shell">
					<header class="dv-portfolio-case__editor-heading">
						<p class="dv-portfolio-case__eyebrow"><?php esc_html_e( 'Sobre o projeto', 'dv-visual' ); ?></p>
						<h2><?php esc_html_e( 'O desafio e a solução.', 'dv-visual' ); ?></h2>
					</header>
					<article class="dv-entry-content dv-portfolio-case__editor">
						<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</article>
				</div>
			</section>
		<?php endif; ?>

		<!-- CAMPOS ACF: desafio, estratégia e solução criativa. -->
		<?php if ( $case_sections ) : ?>
			<section class="dv-portfolio-case__story">
				<div class="dv-portfolio-case__shell">
					<?php $case_index = 0; ?>
					<?php foreach ( $case_sections as $case_title => $case_content ) : ?>
						<?php $case_index++; ?>
						<article>
							<span><?php echo esc_html( str_pad( (string) $case_index, 2, '0', STR_PAD_LEFT ) ); ?></span>
							<h2><?php echo esc_html( $case_title ); ?></h2>
							<div class="dv-entry-content"><?php echo wp_kses_post( $case_content ); ?></div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- RESULTADOS E PALETA: repetidores ACF opcionais. -->
		<?php if ( is_array( $results ) && $results ) : ?>
			<section class="dv-portfolio-case__results">
				<div class="dv-portfolio-case__shell">
					<p class="dv-portfolio-case__eyebrow"><?php esc_html_e( 'Resultados do projeto', 'dv-visual' ); ?></p>
					<div>
						<?php foreach ( $results as $result ) : ?>
							<article>
								<strong><?php echo esc_html( isset( $result['value'] ) ? $result['value'] : '' ); ?></strong>
								<span><?php echo esc_html( isset( $result['label'] ) ? $result['label'] : '' ); ?></span>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( is_array( $palette ) && $palette ) : ?>
			<section class="dv-portfolio-case__palette">
				<div class="dv-portfolio-case__shell">
					<p class="dv-portfolio-case__eyebrow"><?php esc_html_e( 'Identidade visual', 'dv-visual' ); ?></p>
					<h2><?php esc_html_e( 'Paleta do projeto.', 'dv-visual' ); ?></h2>
					<div>
						<?php foreach ( $palette as $color ) : ?>
							<figure style="--dv-project-color:<?php echo esc_attr( isset( $color['color'] ) ? $color['color'] : '#14B8B5' ); ?>">
								<i aria-hidden="true"></i>
								<figcaption><?php echo esc_html( isset( $color['name'] ) && $color['name'] ? $color['name'] : ( isset( $color['color'] ) ? $color['color'] : '' ) ); ?></figcaption>
							</figure>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- GALERIA ACF: grade responsiva em fundo azul profundo. -->
		<?php if ( is_array( $gallery ) && $gallery ) : ?>
			<section class="dv-portfolio-case__gallery">
				<div class="dv-portfolio-case__shell">
					<header>
						<p class="dv-portfolio-case__eyebrow"><?php echo esc_html( $gallery_kicker ); ?></p>
						<h2><?php echo esc_html( $gallery_title ); ?></h2>
						<p>
							<?php
							if ( $gallery_text ) {
								echo wp_kses_post( $gallery_text );
							} else {
								echo esc_html( sprintf( _n( '%s imagem do projeto entregue.', '%s imagens do projeto entregue.', count( $gallery ), 'dv-visual' ), number_format_i18n( count( $gallery ) ) ) );
							}
							?>
						</p>
					</header>
					<div class="dv-portfolio-case__gallery-grid">
						<?php $gallery_index = 0; ?>
						<?php foreach ( $gallery as $image ) : ?>
							<?php
							$gallery_index++;
							$image_data      = diniz_studio_hero_media_data( $image );
							$image_html      = diniz_studio_acf_image( $image, 'large', 'dv-portfolio-case__gallery-image' );
							$image_caption   = $image_data['alt'];
							$image_caption   = ! $image_caption && is_array( $image ) && ! empty( $image['caption'] ) ? $image['caption'] : $image_caption;
							$image_caption   = ! $image_caption && is_array( $image ) && ! empty( $image['title'] ) ? $image['title'] : $image_caption;
							$lightbox_label  = sprintf(
								/* translators: 1: image position, 2: image total. */
								__( 'Ampliar imagem %1$s de %2$s', 'dv-visual' ),
								number_format_i18n( $gallery_index ),
								number_format_i18n( count( $gallery ) )
							);
							?>
							<?php if ( $image_html ) : ?>
								<figure>
									<?php if ( $image_data['url'] ) : ?>
										<a
											href="<?php echo esc_url( $image_data['url'] ); ?>"
											data-dv-lightbox
											data-dv-lightbox-group="portfolio-<?php echo esc_attr( $post_id ); ?>"
											data-dv-lightbox-caption="<?php echo esc_attr( wp_strip_all_tags( (string) $image_caption ) ); ?>"
											aria-label="<?php echo esc_attr( $lightbox_label ); ?>"
											aria-haspopup="dialog"
										>
									<?php endif; ?>
									<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php if ( $image_data['url'] ) : ?></a><?php endif; ?>
								</figure>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- SCROLL CONTAINER ACF: uma apresentação vertical com imagens ou PDFs. -->
		<?php if ( $scroll_slides ) : ?>
			<section class="dv-portfolio-case__scroll<?php echo is_array( $gallery ) && $gallery ? ' dv-portfolio-case__scroll--after-gallery' : ' dv-portfolio-case__scroll--gallery-replacement'; ?>">
				<div class="dv-portfolio-case__shell">
					<header class="dv-project-scroll__heading">
						<div>
							<p class="dv-portfolio-case__eyebrow"><?php echo esc_html( $scroll_kicker ); ?></p>
							<h2><?php echo esc_html( $scroll_title ); ?></h2>
						</div>
						<?php if ( $scroll_text ) : ?>
							<p><?php echo wp_kses_post( $scroll_text ); ?></p>
						<?php endif; ?>
					</header>

					<div class="dv-project-scroll-swiper swiper" data-dv-swiper="project-scroll" aria-label="<?php esc_attr_e( 'Apresentação rolável do projeto', 'dv-visual' ); ?>">
						<div class="swiper-wrapper">
							<div class="swiper-slide dv-project-scroll__slide">
								<?php foreach ( $scroll_slides as $scroll_index => $scroll_slide ) : ?>
									<?php
									$media       = $scroll_slide['media'];
									$item_title  = wp_strip_all_tags( (string) $scroll_slide['title'] );
									$item_title  = $item_title ?: sprintf( __( 'Arquivo %s do projeto', 'dv-visual' ), number_format_i18n( $scroll_index + 1 ) );
									$item_caption = $scroll_slide['caption'];
									?>
									<article class="dv-project-scroll__item<?php echo $media['is_pdf'] ? ' dv-project-scroll__item--pdf' : ' dv-project-scroll__item--image'; ?>">
										<?php if ( $media['is_pdf'] ) : ?>
											<div class="dv-project-scroll__pdf">
												<iframe
													src="<?php echo esc_url( $media['url'] . '#view=FitH&toolbar=0&navpanes=0' ); ?>"
													title="<?php echo esc_attr( $item_title ); ?>"
													loading="lazy"
												></iframe>
												<a href="<?php echo esc_url( $media['url'] ); ?>" target="_blank" rel="noopener">
													<?php esc_html_e( 'Abrir PDF em nova aba', 'dv-visual' ); ?> <span aria-hidden="true">↗</span>
												</a>
											</div>
										<?php else : ?>
											<figure>
												<?php echo diniz_studio_acf_image( $scroll_slide['file_value'], 'full', 'dv-project-scroll__image' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</figure>
										<?php endif; ?>

										<?php if ( $item_title || $item_caption ) : ?>
											<footer>
												<strong><?php echo esc_html( $item_title ); ?></strong>
												<?php if ( $item_caption ) : ?><p><?php echo wp_kses_post( $item_caption ); ?></p><?php endif; ?>
											</footer>
										<?php endif; ?>
									</article>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="swiper-scrollbar" aria-hidden="true"></div>
					</div>
					<p class="dv-project-scroll__hint"><?php esc_html_e( 'Arraste ou use a roda do mouse para percorrer a apresentação.', 'dv-visual' ); ?></p>
				</div>
			</section>
		<?php endif; ?>

		<?php
		if ( $video ) :
			$video_html    = filter_var( $video, FILTER_VALIDATE_URL ) ? wp_oembed_get( $video ) : $video;
			$video_allowed = array(
				'iframe' => array(
					'src'             => true,
					'width'           => true,
					'height'          => true,
					'frameborder'     => true,
					'allow'           => true,
					'allowfullscreen' => true,
					'title'           => true,
					'loading'         => true,
				),
			);
			?>
			<section class="dv-portfolio-case__video">
				<div class="dv-portfolio-case__shell"><?php echo wp_kses( $video_html, $video_allowed ); ?></div>
			</section>
		<?php endif; ?>

		<!-- PROJETOS RELACIONADOS: prioriza projetos do mesmo segmento. -->
		<?php if ( $related->have_posts() ) : ?>
			<section class="dv-portfolio-case__related">
				<div class="dv-portfolio-case__shell">
					<header>
						<div>
							<p class="dv-portfolio-case__eyebrow"><?php esc_html_e( 'Mais cases', 'dv-visual' ); ?></p>
							<h2><?php esc_html_e( 'Projetos do mesmo setor.', 'dv-visual' ); ?></h2>
						</div>
					</header>
					<div class="dv-portfolio-grid">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							echo diniz_studio_portfolio_card( get_the_ID(), 'related' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						endwhile;
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>

		<!-- ENCERRAMENTO: o único botão "Ver portfólio" da página. -->
		<section class="dv-portfolio-case__final">
			<div class="dv-portfolio-case__shell">
				<div>
					<p class="dv-portfolio-case__eyebrow"><?php esc_html_e( 'Próximo projeto', 'dv-visual' ); ?></p>
					<h2><?php esc_html_e( 'Continue explorando os nossos cases.', 'dv-visual' ); ?></h2>
				</div>
				<a href="<?php echo esc_url( $archive_url ); ?>"><?php esc_html_e( 'Ver portfólio', 'dv-visual' ); ?> <span aria-hidden="true">↗</span></a>
			</div>
		</section>
	</main>
	<?php
	return ob_get_clean();
}

/**
 * Complete singular view shared by all custom content types.
 *
 * @return string
 */
function diniz_studio_cpt_single_shortcode() {
	$post_id   = get_queried_object_id();
	$post_type = get_post_type( $post_id );
	$configs   = diniz_studio_content_type_config();

	if ( 'portfolio' === $post_type ) {
		return diniz_studio_portfolio_single_shortcode();
	}

	if ( ! $post_id || ! isset( $configs[ $post_type ] ) ) {
		return '';
	}

	$config          = $configs[ $post_type ];
	$taxonomy        = diniz_studio_primary_taxonomy( $post_type );
	$hero_enabled    = (bool) diniz_studio_content_field( 'hero_enabled', $post_id );
	$hero_title      = $hero_enabled ? diniz_studio_content_field( 'hero_title', $post_id ) : '';
	$hero_highlight  = $hero_enabled ? diniz_studio_content_field( 'hero_highlight', $post_id ) : '';
	$hero_text       = $hero_enabled ? diniz_studio_content_field( 'hero_text', $post_id ) : '';
	$hero_style      = $hero_enabled ? sanitize_html_class( (string) diniz_studio_content_field( 'hero_style', $post_id ) ) : 'default';
	$hero_primary    = $hero_enabled ? diniz_studio_content_field( 'hero_primary_cta', $post_id ) : '';
	$hero_secondary  = $hero_enabled ? diniz_studio_content_field( 'hero_secondary_cta', $post_id ) : '';
	$hero_metrics    = $hero_enabled ? diniz_studio_content_field( 'hero_metrics', $post_id ) : array();
	$hero_video      = $hero_enabled ? diniz_studio_content_field( 'hero_video', $post_id ) : '';
	$summary         = $hero_text ?: ( diniz_studio_content_field( 'content_summary', $post_id ) ?: get_the_excerpt( $post_id ) );
	$badge           = $hero_enabled && diniz_studio_content_field( 'hero_kicker', $post_id ) ? diniz_studio_content_field( 'hero_kicker', $post_id ) : ( diniz_studio_content_field( 'content_badge', $post_id ) ?: $config['singular'] );
	$facts           = diniz_studio_content_facts( $post_id, $post_type );
	$features        = diniz_studio_content_field( 'content_features', $post_id );
	$gallery         = diniz_studio_content_field( 'content_gallery', $post_id );
	$external_url    = diniz_studio_content_field( 'content_url', $post_id );
	$featured_image  = get_the_post_thumbnail(
		$post_id,
		'full',
		array(
			'class'   => 'dv-cpt-single__image',
			'loading' => 'eager',
		)
	);
	$automatic_toc   = diniz_studio_content_field( 'automatic_toc', $post_id );
	$article_summary = diniz_studio_content_field( 'article_summary', $post_id );
	$article_faq     = diniz_studio_content_field( 'article_faq', $post_id );
	$article_cta_title = diniz_studio_content_field( 'article_cta_title', $post_id );
	$article_cta_text  = diniz_studio_content_field( 'article_cta_text', $post_id );
	$article_cta_link  = diniz_studio_content_field( 'article_cta_link', $post_id );
	$display_title   = $hero_title ?: get_the_title( $post_id );
	$title_html      = esc_html( $display_title );

	if ( $hero_highlight ) {
		$escaped_highlight = esc_html( $hero_highlight );
		$title_html        = str_replace( $escaped_highlight, '<mark>' . $escaped_highlight . '</mark>', $title_html );
	}

	if ( is_array( $hero_metrics ) ) {
		foreach ( $hero_metrics as $hero_metric ) {
			if ( count( $facts ) >= 4 || empty( $hero_metric['value'] ) ) {
				break;
			}
			$facts[] = array(
				'label' => isset( $hero_metric['label'] ) ? $hero_metric['label'] : 'Destaque',
				'value' => trim( (string) $hero_metric['value'] . ( ! empty( $hero_metric['trend'] ) ? ' ' . $hero_metric['trend'] : '' ) ),
			);
		}
	}

	if ( 'portfolio' === $post_type ) {
		$project_gallery = diniz_studio_content_field( 'project_gallery', $post_id );
		$gallery         = $project_gallery ?: $gallery;
		$external_url    = diniz_studio_content_field( 'project_url', $post_id ) ?: $external_url;
	}

	if ( $hero_enabled && diniz_studio_content_field( 'hero_image', $post_id ) ) {
		$featured_image = diniz_studio_acf_image( diniz_studio_content_field( 'hero_image', $post_id ), 'full', 'dv-cpt-single__image' );
	}

	if ( ! $featured_image && in_array( $post_type, array( 'team', 'testimonial' ), true ) ) {
		$featured_image = diniz_studio_acf_image( diniz_studio_content_field( 'person_avatar', $post_id ), 'full', 'dv-cpt-single__image' );
	}
	if ( ! $featured_image ) {
		$featured_image = diniz_studio_acf_image( diniz_studio_content_field( 'content_logo', $post_id ), 'full', 'dv-cpt-single__image dv-cpt-single__image--contain' );
	}

	ob_start();
	?>
	<main class="dv-cpt-single dv-cpt-single--<?php echo esc_attr( $post_type ); ?>">
		<section class="dv-cpt-single__hero dv-cpt-single__hero--<?php echo esc_attr( $hero_style ?: 'default' ); ?>">
			<div class="dv-cpt-shell">
				<?php
				echo diniz_studio_breadcrumbs(
					array(
						array( 'label' => __( 'Início', 'dv-visual' ), 'url' => home_url( '/' ) ),
						array( 'label' => $config['name'], 'url' => get_post_type_archive_link( $post_type ) ),
						array( 'label' => get_the_title( $post_id ) ),
					)
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<div class="dv-cpt-single__heading">
					<div>
						<p class="dv-kicker dv-kicker-dark"><?php echo esc_html( $badge ); ?></p>
						<h1><?php echo wp_kses( $title_html, array( 'mark' => array() ) ); ?></h1>
						<?php if ( $summary ) : ?><p class="dv-cpt-single__lead"><?php echo esc_html( wp_strip_all_tags( (string) $summary ) ); ?></p><?php endif; ?>
						<?php if ( is_array( $hero_primary ) || is_array( $hero_secondary ) ) : ?>
							<div class="dv-cpt-single__actions">
								<?php if ( is_array( $hero_primary ) && ! empty( $hero_primary['url'] ) ) : ?><a class="dv-cpt-single__action-primary" href="<?php echo esc_url( $hero_primary['url'] ); ?>" target="<?php echo esc_attr( ! empty( $hero_primary['target'] ) ? $hero_primary['target'] : '_self' ); ?>"><?php echo esc_html( ! empty( $hero_primary['title'] ) ? $hero_primary['title'] : 'Saiba mais' ); ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
								<?php if ( is_array( $hero_secondary ) && ! empty( $hero_secondary['url'] ) ) : ?><a class="dv-cpt-single__action-secondary" href="<?php echo esc_url( $hero_secondary['url'] ); ?>" target="<?php echo esc_attr( ! empty( $hero_secondary['target'] ) ? $hero_secondary['target'] : '_self' ); ?>"><?php echo esc_html( ! empty( $hero_secondary['title'] ) ? $hero_secondary['title'] : 'Explorar' ); ?></a><?php endif; ?>
							</div>
						<?php endif; ?>
						<?php echo diniz_studio_term_chips( $post_id, $taxonomy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<span class="dv-cpt-single__monogram" aria-hidden="true"><?php echo esc_html( strtolower( substr( $config['singular'], 0, 1 ) ) ); ?></span>
				</div>
			</div>
		</section>

		<div class="dv-cpt-shell dv-cpt-single__body">
			<?php
			$hero_video_url = is_numeric( $hero_video ) ? wp_get_attachment_url( (int) $hero_video ) : ( is_array( $hero_video ) && ! empty( $hero_video['url'] ) ? $hero_video['url'] : ( is_string( $hero_video ) ? $hero_video : '' ) );
			?>
			<?php if ( $hero_video_url ) : ?>
				<figure class="dv-cpt-single__visual"><video class="dv-cpt-single__video" src="<?php echo esc_url( $hero_video_url ); ?>" autoplay muted loop playsinline controls></video></figure>
			<?php elseif ( $featured_image ) : ?>
				<figure class="dv-cpt-single__visual"><?php echo $featured_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></figure>
			<?php endif; ?>

			<?php if ( $facts ) : ?>
				<div class="dv-cpt-facts">
					<?php foreach ( $facts as $fact ) : ?>
						<div class="dv-glass-card"><small><?php echo esc_html( $fact['label'] ); ?></small><strong><?php echo esc_html( $fact['value'] ); ?></strong></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="dv-cpt-single__layout">
				<article class="dv-entry-content" data-dv-toc="<?php echo $automatic_toc ? 'true' : 'false'; ?>">
					<?php if ( $article_summary ) : ?>
						<div class="dv-article-summary"><span><?php esc_html_e( 'Em resumo', 'dv-visual' ); ?></span><?php echo wp_kses_post( $article_summary ); ?></div>
					<?php endif; ?>
					<?php echo apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</article>
				<aside class="dv-cpt-single__aside">
					<?php if ( $automatic_toc ) : ?><nav class="dv-article-toc dv-glass-card" aria-label="<?php esc_attr_e( 'Nesta página', 'dv-visual' ); ?>"><strong><?php esc_html_e( 'Nesta página', 'dv-visual' ); ?></strong><ol></ol></nav><?php endif; ?>
					<?php if ( $external_url ) : ?>
						<a class="dv-cpt-external" href="<?php echo esc_url( $external_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( in_array( $post_type, array( 'tool', 'product' ), true ) ? 'Acessar agora' : 'Visitar projeto' ); ?> <span aria-hidden="true">↗</span></a>
					<?php endif; ?>
					<a class="dv-cpt-back" href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>">← <?php echo esc_html( sprintf( 'Ver %s', strtolower( $config['name'] ) ) ); ?></a>
				</aside>
			</div>

			<?php if ( is_array( $features ) && $features ) : ?>
				<section class="dv-cpt-features">
					<p class="dv-kicker"><?php echo esc_html( in_array( $post_type, array( 'portfolio', 'testimonial', 'award' ), true ) ? 'Resultados' : 'O que você encontra' ); ?></p>
					<h2><?php esc_html_e( 'Principais destaques', 'dv-visual' ); ?></h2>
					<div class="dv-cpt-features__grid">
						<?php foreach ( $features as $feature ) : ?>
							<article class="dv-glass-card"><span aria-hidden="true">+</span><h3><?php echo esc_html( isset( $feature['title'] ) ? $feature['title'] : '' ); ?></h3><p><?php echo esc_html( isset( $feature['text'] ) ? $feature['text'] : '' ); ?></p></article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( is_array( $article_faq ) && $article_faq ) : ?>
				<section class="dv-article-faq">
					<p class="dv-kicker"><?php esc_html_e( 'Dúvidas frequentes', 'dv-visual' ); ?></p>
					<h2><?php esc_html_e( 'Respostas rápidas para continuar.', 'dv-visual' ); ?></h2>
					<div>
						<?php foreach ( $article_faq as $faq ) : ?>
							<details><summary><?php echo esc_html( isset( $faq['question'] ) ? $faq['question'] : '' ); ?></summary><p><?php echo esc_html( isset( $faq['answer'] ) ? $faq['answer'] : '' ); ?></p></details>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( $article_cta_title || $article_cta_text || ( is_array( $article_cta_link ) && ! empty( $article_cta_link['url'] ) ) ) : ?>
				<section class="dv-article-cta">
					<div><p class="dv-kicker dv-kicker-dark"><?php esc_html_e( 'Próximo passo', 'dv-visual' ); ?></p><h2><?php echo esc_html( $article_cta_title ?: 'Vamos transformar conhecimento em ação?' ); ?></h2><?php if ( $article_cta_text ) : ?><p><?php echo esc_html( $article_cta_text ); ?></p><?php endif; ?></div>
					<?php if ( is_array( $article_cta_link ) && ! empty( $article_cta_link['url'] ) ) : ?><a href="<?php echo esc_url( $article_cta_link['url'] ); ?>" target="<?php echo esc_attr( ! empty( $article_cta_link['target'] ) ? $article_cta_link['target'] : '_self' ); ?>"><?php echo esc_html( ! empty( $article_cta_link['title'] ) ? $article_cta_link['title'] : 'Continuar' ); ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( 'portfolio' === $post_type ) : ?>
				<?php
				$case_sections = array(
					'Desafio'          => diniz_studio_content_field( 'project_challenge', $post_id ),
					'Estratégia'       => diniz_studio_content_field( 'project_strategy', $post_id ),
					'Solução criativa' => diniz_studio_content_field( 'project_solution', $post_id ),
				);
				?>
				<?php if ( array_filter( $case_sections ) ) : ?>
					<section class="dv-case-story">
						<?php foreach ( $case_sections as $case_title => $case_content ) : ?>
							<?php if ( $case_content ) : ?><article><span><?php echo esc_html( str_pad( (string) ( array_search( $case_title, array_keys( $case_sections ), true ) + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><h2><?php echo esc_html( $case_title ); ?></h2><div><?php echo wp_kses_post( $case_content ); ?></div></article><?php endif; ?>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>
				<?php
				$results = diniz_studio_content_field( 'project_results', $post_id );
				if ( is_array( $results ) && $results ) :
					?>
					<section class="dv-case-results">
						<?php foreach ( $results as $result ) : ?>
							<div><strong><?php echo esc_html( isset( $result['value'] ) ? $result['value'] : '' ); ?></strong><span><?php echo esc_html( isset( $result['label'] ) ? $result['label'] : '' ); ?></span></div>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>
				<?php
				$palette = diniz_studio_content_field( 'project_palette', $post_id );
				if ( is_array( $palette ) && $palette ) :
					?>
					<section class="dv-case-palette"><p class="dv-kicker"><?php esc_html_e( 'Paleta do projeto', 'dv-visual' ); ?></p><div>
						<?php foreach ( $palette as $color ) : ?><figure style="--dv-case-color:<?php echo esc_attr( isset( $color['color'] ) ? $color['color'] : '#14B8B5' ); ?>"><i></i><figcaption><?php echo esc_html( isset( $color['name'] ) ? $color['name'] : ( isset( $color['color'] ) ? $color['color'] : '' ) ); ?></figcaption></figure><?php endforeach; ?>
					</div></section>
				<?php endif; ?>
			<?php endif; ?>

			<?php echo diniz_studio_content_gallery( $gallery ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php
			$project_video = 'portfolio' === $post_type ? diniz_studio_content_field( 'project_video', $post_id ) : '';
			if ( $project_video ) :
				$project_video_html = filter_var( $project_video, FILTER_VALIDATE_URL ) ? wp_oembed_get( $project_video ) : $project_video;
				$video_allowed      = array(
					'iframe' => array(
						'src'             => true,
						'width'           => true,
						'height'          => true,
						'frameborder'     => true,
						'allow'           => true,
						'allowfullscreen' => true,
						'title'           => true,
						'loading'         => true,
					),
				);
				?>
				<section class="dv-case-video"><?php echo wp_kses( $project_video_html, $video_allowed ); ?></section>
			<?php endif; ?>

			<?php
			$related = new WP_Query(
				array(
					'post_type'           => $post_type,
					'post_status'         => 'publish',
					'posts_per_page'      => 3,
					'post__not_in'        => array( $post_id ),
					'ignore_sticky_posts' => true,
				)
			);
			if ( $related->have_posts() ) :
				?>
				<section class="dv-cpt-related">
					<div class="dv-cpt-related__heading"><div><p class="dv-kicker"><?php esc_html_e( 'Continue explorando', 'dv-visual' ); ?></p><h2><?php echo esc_html( sprintf( 'Mais %s', strtolower( $config['name'] ) ) ); ?></h2></div><a href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>"><?php esc_html_e( 'Ver todos', 'dv-visual' ); ?> ↗</a></div>
					<div class="dv-cpt-grid">
						<?php
						$related_index = 0;
						while ( $related->have_posts() ) :
							$related->the_post();
							$related_index++;
							echo diniz_studio_content_card( get_the_ID(), $related_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						endwhile;
						?>
					</div>
				</section>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</main>
	<?php
	return ob_get_clean();
}
add_shortcode( 'dv_cpt_single', 'diniz_studio_cpt_single_shortcode' );

/**
 * Highlight a configurable fragment inside a hero title.
 *
 * @param string $title     Complete title.
 * @param string $highlight Fragment rendered with the accent color.
 * @return string
 */
function diniz_studio_hero_title_html( $title, $highlight = '' ) {
	$title     = trim( (string) $title );
	$highlight = trim( (string) $highlight );
	$position  = $highlight ? stripos( $title, $highlight ) : false;

	if ( false === $position ) {
		return esc_html( $title );
	}

	return esc_html( substr( $title, 0, $position ) )
		. '<mark>' . esc_html( substr( $title, $position, strlen( $highlight ) ) ) . '</mark>'
		. esc_html( substr( $title, $position + strlen( $highlight ) ) );
}

/**
 * Return the curated home hero used until custom ACF slides are published.
 *
 * @return array
 */
function diniz_studio_default_hero_slides() {
	$portfolio_url = get_post_type_archive_link( 'portfolio' ) ?: home_url( '/portfolio/' );
	$services_url  = get_post_type_archive_link( 'service' ) ?: home_url( '/solucao/' );
	$proposal_url  = diniz_studio_menu_page_url( 'proposta' );

	return array(
		array(
			'kicker'           => 'Estratégia · Design · Digital',
			'title'            => 'Sua marca pronta para ser lembrada.',
			'highlight'        => 'ser lembrada.',
			'text'             => 'Estratégia, identidade visual e experiências digitais que transformam percepção em valor para o seu negócio.',
			'primary_cta'      => array( 'title' => 'Explorar projetos', 'url' => $portfolio_url, 'target' => '_self' ),
			'secondary_cta'    => array( 'title' => 'Iniciar um projeto', 'url' => $proposal_url, 'target' => '_self' ),
			'image'            => 0,
			'image_mobile'     => 0,
			'background'       => 0,
			'background_mobile' => 0,
			'theme'            => 'aqua',
			'project_label'    => 'Projeto em destaque',
			'project_title'    => 'Nova identidade',
			'project_category' => 'Estratégia + Design',
			'metric_value'     => '+68%',
			'metric_label'     => 'reconhecimento',
			'duration'         => 6800,
		),
		array(
			'kicker'           => 'Branding com intenção',
			'title'            => 'Identidades que criam presença.',
			'highlight'        => 'criam presença.',
			'text'             => 'Posicionamento, linguagem visual e sistemas de marca coerentes em todos os pontos de contato.',
			'primary_cta'      => array( 'title' => 'Conhecer o portfólio', 'url' => $portfolio_url, 'target' => '_self' ),
			'secondary_cta'    => array( 'title' => 'Ver soluções', 'url' => $services_url, 'target' => '_self' ),
			'image'            => 0,
			'image_mobile'     => 0,
			'background'       => 0,
			'background_mobile' => 0,
			'theme'            => 'teal',
			'project_label'    => 'Sistema de marca',
			'project_title'    => 'Design reconhecível',
			'project_category' => 'Branding + Identidade',
			'metric_value'     => '360°',
			'metric_label'     => 'consistência visual',
			'duration'         => 6200,
		),
		array(
			'kicker'           => 'Sites & produtos digitais',
			'title'            => 'Experiências que aproximam e convertem.',
			'highlight'        => 'aproximam e convertem.',
			'text'             => 'Sites rápidos, responsivos e fáceis de gerenciar, construídos para dar força à sua próxima fase.',
			'primary_cta'      => array( 'title' => 'Ver soluções digitais', 'url' => $services_url, 'target' => '_self' ),
			'secondary_cta'    => array( 'title' => 'Solicitar proposta', 'url' => $proposal_url, 'target' => '_self' ),
			'image'            => 0,
			'image_mobile'     => 0,
			'background'       => 0,
			'background_mobile' => 0,
			'theme'            => 'navy',
			'project_label'    => 'Experiência digital',
			'project_title'    => 'Sites com propósito',
			'project_category' => 'UX/UI + Tecnologia',
			'metric_value'     => '100%',
			'metric_label'     => 'responsivo',
			'duration'         => 6400,
		),
	);
}

/**
 * Render one link from an ACF link field.
 *
 * @param mixed  $link       ACF link field.
 * @param string $class_name Button class.
 * @return string
 */
function diniz_studio_hero_link( $link, $class_name ) {
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return '';
	}

	$target = ! empty( $link['target'] ) ? $link['target'] : '_self';
	$rel    = '_blank' === $target ? ' rel="noopener"' : '';

	return sprintf(
		'<a class="%1$s" href="%2$s" target="%3$s"%4$s>%5$s <span aria-hidden="true">↗</span></a>',
		esc_attr( $class_name ),
		esc_url( $link['url'] ),
		esc_attr( $target ),
		$rel,
		esc_html( ! empty( $link['title'] ) ? $link['title'] : __( 'Saiba mais', 'dv-visual' ) )
	);
}

/**
 * Normalize an ACF image into responsive source data.
 *
 * @param mixed $image_value ACF image value.
 * @return array
 */
function diniz_studio_hero_media_data( $image_value ) {
	$attachment_id = is_numeric( $image_value ) ? (int) $image_value : ( is_array( $image_value ) && ! empty( $image_value['ID'] ) ? (int) $image_value['ID'] : 0 );
	$data          = array(
		'url'    => '',
		'srcset' => '',
		'alt'    => '',
		'width'  => 0,
		'height' => 0,
	);

	if ( $attachment_id ) {
		$source = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( $source ) {
			$data['url']    = $source[0];
			$data['width']  = (int) $source[1];
			$data['height'] = (int) $source[2];
		}
		$data['srcset'] = wp_get_attachment_image_srcset( $attachment_id, 'full' ) ?: '';
		$data['alt']    = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		return $data;
	}

	if ( is_array( $image_value ) && ! empty( $image_value['url'] ) ) {
		$data['url']    = $image_value['url'];
		$data['alt']    = ! empty( $image_value['alt'] ) ? $image_value['alt'] : '';
		$data['width']  = ! empty( $image_value['width'] ) ? (int) $image_value['width'] : 0;
		$data['height'] = ! empty( $image_value['height'] ) ? (int) $image_value['height'] : 0;
		return $data;
	}

	if ( is_string( $image_value ) ) {
		$data['url'] = $image_value;
	}

	return $data;
}

/**
 * Render desktop and mobile ACF images as one responsive picture.
 *
 * @param mixed  $desktop_value Desktop ACF image.
 * @param mixed  $mobile_value  Mobile ACF image.
 * @param string $picture_class Picture class.
 * @param string $image_class   Image class.
 * @param bool   $is_first      Whether this is the initial slide.
 * @param bool   $decorative    Whether the image is decorative.
 * @return string
 */
function diniz_studio_hero_picture( $desktop_value, $mobile_value, $picture_class, $image_class, $is_first = false, $decorative = false ) {
	$desktop = diniz_studio_hero_media_data( $desktop_value ?: $mobile_value );
	$mobile  = diniz_studio_hero_media_data( $mobile_value ?: $desktop_value );

	if ( empty( $desktop['url'] ) ) {
		return '';
	}

	$is_background  = false !== strpos( $picture_class, 'background' ) || false !== strpos( $picture_class, '__media' );
	$desktop_sizes  = $is_background ? '100vw' : '(max-width: 920px) 100vw, 50vw';
	$mobile_srcset  = $mobile['srcset'] ?: $mobile['url'];
	$desktop_srcset = $desktop['srcset'];
	$alt            = $decorative ? '' : ( $desktop['alt'] ?: $mobile['alt'] );
	$dimensions     = $desktop['width'] && $desktop['height']
		? sprintf( ' width="%1$d" height="%2$d"', $desktop['width'], $desktop['height'] )
		: '';
	$aria_hidden    = $decorative ? ' aria-hidden="true"' : '';
	$fetchpriority  = $is_first ? ' fetchpriority="high"' : '';

	return sprintf(
		'<picture class="%1$s"%2$s><source media="(max-width: 767px)" srcset="%3$s" sizes="100vw"><img class="%4$s" src="%5$s"%6$s sizes="%7$s" alt="%8$s" loading="%9$s" decoding="async"%10$s%11$s></picture>',
		esc_attr( $picture_class ),
		$aria_hidden,
		esc_attr( $mobile_srcset ),
		esc_attr( $image_class ),
		esc_url( $desktop['url'] ),
		$desktop_srcset ? ' srcset="' . esc_attr( $desktop_srcset ) . '"' : '',
		esc_attr( $desktop_sizes ),
		esc_attr( $alt ),
		$is_first ? 'eager' : 'lazy',
		$fetchpriority,
		$dimensions
	);
}

/**
 * Sanitize an image focal position stored by ACF.
 *
 * @param mixed $position ACF select value.
 * @return string
 */
function diniz_studio_hero_image_position( $position ) {
	$allowed = array( 'center center', 'center top', 'center bottom', 'left center', 'right center' );
	return in_array( $position, $allowed, true ) ? $position : 'center center';
}

/**
 * Customizable Swiper hero for the home page.
 *
 * @return string
 */
function diniz_studio_hero_slider_shortcode() {
	/*
	 * Primary source: Home — Slides Custom Post Type.
	 * Legacy ACF option slides remain as a safe fallback for older installs.
	 */
	$slides = function_exists( 'diniz_studio_get_managed_home_slides' ) ? diniz_studio_get_managed_home_slides() : array();
	if ( ! $slides ) {
		$slides = diniz_studio_content_field( 'dv_hero_slides', 'option' );
		$slides = is_array( $slides ) && $slides ? $slides : diniz_studio_default_hero_slides();
	}

	$autoplay_setting = function_exists( 'get_field' ) ? get_field( 'dv_hero_autoplay', 'option' ) : null;
	$autoplay         = null === $autoplay_setting || '' === $autoplay_setting ? true : (bool) $autoplay_setting;
	$delay            = absint( diniz_studio_content_field( 'dv_hero_delay', 'option' ) );
	$delay            = max( 2500, min( 20000, $delay ?: 5000 ) );

	ob_start();
	?>
	<section class="dv-hero-slider" aria-label="<?php esc_attr_e( 'Destaques do site', 'dv-visual' ); ?>">
		<div class="dv-hero-swiper swiper" data-dv-swiper="hero" data-dv-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>" data-dv-delay="<?php echo esc_attr( $delay ); ?>">
			<div class="swiper-wrapper">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php
					$slide                       = is_array( $slide ) ? $slide : array();
					$title                       = ! empty( $slide['title'] ) ? $slide['title'] : get_bloginfo( 'name' );
					$theme                       = ! empty( $slide['theme'] ) ? sanitize_html_class( $slide['theme'] ) : 'aqua';
					$duration                    = ! empty( $slide['duration'] ) ? max( 2500, min( 20000, absint( $slide['duration'] ) ) ) : 0;
					$image_desktop               = isset( $slide['image'] ) ? $slide['image'] : 0;
					$image_mobile                = isset( $slide['image_mobile'] ) ? $slide['image_mobile'] : 0;
					$background_desktop          = isset( $slide['background'] ) ? $slide['background'] : 0;
					$background_mobile           = isset( $slide['background_mobile'] ) ? $slide['background_mobile'] : 0;
					$banner                      = diniz_studio_hero_picture(
						$background_desktop ?: $image_desktop,
						$background_mobile ?: $image_mobile,
						'dv-hero-slide__media',
						'dv-hero-slide__media-image',
						0 === $index,
						true
					);
					$art                         = $background_desktop && $image_desktop
						? diniz_studio_hero_picture(
							$image_desktop,
							$image_mobile,
							'dv-hero-slide__art-picture',
							'dv-hero-slide__art-image',
							0 === $index,
							true
						)
						: '';
					$image_position_desktop      = diniz_studio_hero_image_position( isset( $slide['image_position_desktop'] ) ? $slide['image_position_desktop'] : '' );
					$image_position_mobile       = diniz_studio_hero_image_position( isset( $slide['image_position_mobile'] ) ? $slide['image_position_mobile'] : '' );
					$background_position_desktop = diniz_studio_hero_image_position( isset( $slide['background_position_desktop'] ) ? $slide['background_position_desktop'] : '' );
					$background_position_mobile  = diniz_studio_hero_image_position( isset( $slide['background_position_mobile'] ) ? $slide['background_position_mobile'] : '' );
					$overlay_opacity             = isset( $slide['overlay_opacity'] ) && '' !== $slide['overlay_opacity'] ? absint( $slide['overlay_opacity'] ) : 58;
					$overlay_opacity             = max( 0, min( 90, $overlay_opacity ) ) / 100;
					$text_color                  = ! empty( $slide['text_color'] ) ? sanitize_hex_color( $slide['text_color'] ) : '#ffffff';
					$text_color                  = $text_color ?: '#ffffff';
					$slide_style                 = sprintf(
						'--dv-project-position-desktop:%1$s;--dv-project-position-mobile:%2$s;--dv-background-position-desktop:%3$s;--dv-background-position-mobile:%4$s;--dv-hero-overlay:%5$s;--dv-hero-text:%6$s',
						$image_position_desktop,
						$image_position_mobile,
						$background_position_desktop,
						$background_position_mobile,
						number_format( $overlay_opacity, 2, '.', '' ),
						$text_color
					);
					?>
					<article class="swiper-slide dv-hero-slide dv-hero-slide--<?php echo esc_attr( $theme ); ?>" aria-roledescription="<?php esc_attr_e( 'slide', 'dv-visual' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( '%1$d de %2$d', 'dv-visual' ), $index + 1, count( $slides ) ) ); ?>"<?php echo $duration ? ' data-swiper-autoplay="' . esc_attr( $duration ) . '"' : ''; ?> style="<?php echo esc_attr( $slide_style ); ?>">
						<?php echo $banner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div class="dv-hero-slide__inner">
							<div class="dv-hero-slide__content">
								<?php if ( ! empty( $slide['kicker'] ) ) : ?><p class="dv-kicker dv-kicker-dark"><?php echo esc_html( $slide['kicker'] ); ?></p><?php endif; ?>
								<h1 class="dv-hero-title"><?php echo diniz_studio_hero_title_html( $title, isset( $slide['highlight'] ) ? $slide['highlight'] : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
								<?php if ( ! empty( $slide['text'] ) ) : ?><p class="dv-hero-copy"><?php echo esc_html( $slide['text'] ); ?></p><?php endif; ?>
								<div class="dv-hero-actions">
									<?php echo diniz_studio_hero_link( isset( $slide['primary_cta'] ) ? $slide['primary_cta'] : array(), 'dv-hero-action dv-hero-action--primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php echo diniz_studio_hero_link( isset( $slide['secondary_cta'] ) ? $slide['secondary_cta'] : array(), 'dv-hero-action dv-hero-action--secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<?php if ( ! empty( $slide['project_label'] ) || ! empty( $slide['project_title'] ) || ! empty( $slide['metric_value'] ) ) : ?>
									<div class="dv-hero-meta" aria-label="<?php esc_attr_e( 'Detalhes do destaque', 'dv-visual' ); ?>">
										<?php if ( ! empty( $slide['project_label'] ) ) : ?><span><?php echo esc_html( $slide['project_label'] ); ?></span><?php endif; ?>
										<?php if ( ! empty( $slide['project_title'] ) ) : ?><strong><?php echo esc_html( $slide['project_title'] ); ?></strong><?php endif; ?>
										<?php if ( ! empty( $slide['project_category'] ) ) : ?><small><?php echo esc_html( $slide['project_category'] ); ?></small><?php endif; ?>
										<?php if ( ! empty( $slide['metric_value'] ) ) : ?><b><?php echo esc_html( $slide['metric_value'] ); ?><?php if ( ! empty( $slide['metric_label'] ) ) : ?> <em><?php echo esc_html( $slide['metric_label'] ); ?></em><?php endif; ?></b><?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
							<?php if ( $art ) : ?><div class="dv-hero-slide__art"><?php echo $art; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
			<div class="dv-hero-controls dv-swiper-controls">
				<button class="dv-swiper-button dv-swiper-prev" type="button" aria-label="<?php esc_attr_e( 'Slide anterior', 'dv-visual' ); ?>"><span aria-hidden="true">←</span></button>
				<div class="dv-swiper-pagination swiper-pagination"></div>
				<button class="dv-swiper-button dv-swiper-next" type="button" aria-label="<?php esc_attr_e( 'Próximo slide', 'dv-visual' ); ?>"><span aria-hidden="true">→</span></button>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'dv_hero_slider', 'diniz_studio_hero_slider_shortcode' );

/**
 * Dynamic client logo strip for the home page.
 *
 * @return string
 */
function diniz_studio_clients_strip_shortcode() {
	$clients = new WP_Query(
		array(
			'post_type'      => 'client',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);

	if ( ! $clients->have_posts() ) {
		return current_user_can( 'edit_posts' ) ? '<div class="dv-trust-strip"><p class="dv-dynamic-hint">Cadastre clientes para preencher a faixa de confiança.</p></div>' : '';
	}

	ob_start();
	?>
	<?php
	$brands_title     = diniz_studio_content_field( 'dv_brands_title', 'option' ) ?: __( 'Marcas que confiam no nosso trabalho', 'dv-visual' );
	$autoplay_setting = function_exists( 'get_field' ) ? get_field( 'dv_brands_autoplay', 'option' ) : null;
	$autoplay         = null === $autoplay_setting || '' === $autoplay_setting ? true : (bool) $autoplay_setting;
	$delay            = absint( diniz_studio_content_field( 'dv_brands_delay', 'option' ) );
	$delay            = max( 1200, min( 12000, $delay ?: 2400 ) );
	?>
	<section class="dv-trust-strip" aria-label="<?php esc_attr_e( 'Clientes e parceiros', 'dv-visual' ); ?>">
		<div class="dv-trust-inner">
			<div class="dv-trust-heading">
				<p><?php echo esc_html( $brands_title ); ?></p>
			</div>
			<div class="dv-client-swiper swiper" data-dv-swiper="clients" data-dv-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>" data-dv-delay="<?php echo esc_attr( $delay ); ?>">
				<div class="swiper-wrapper">
					<?php
					while ( $clients->have_posts() ) :
						$clients->the_post();
						$logo         = diniz_studio_acf_image( diniz_studio_content_field( 'content_logo', get_the_ID() ), 'medium', 'dv-client-logo' );
						$logo         = $logo ?: get_the_post_thumbnail( get_the_ID(), 'medium', array( 'class' => 'dv-client-logo', 'loading' => 'lazy' ) );
						$external_url = diniz_studio_content_field( 'content_url', get_the_ID() );
						$client_url   = $external_url ?: get_permalink();
						$target       = $external_url ? '_blank' : '_self';
						$rel          = $external_url ? ' rel="noopener"' : '';
						?>
						<div class="swiper-slide">
							<a href="<?php echo esc_url( $client_url ); ?>" target="<?php echo esc_attr( $target ); ?>"<?php echo $rel; ?> aria-label="<?php echo esc_attr( get_the_title() ); ?>">
								<?php echo $logo ?: '<strong>' . esc_html( get_the_title() ) . '</strong>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						</div>
					<?php endwhile; ?>
				</div>
				<div class="dv-client-controls dv-swiper-controls">
					<button class="dv-swiper-button dv-swiper-prev" type="button" aria-label="<?php esc_attr_e( 'Marca anterior', 'dv-visual' ); ?>"><span aria-hidden="true">←</span></button>
					<div class="dv-swiper-pagination swiper-pagination"></div>
					<button class="dv-swiper-button dv-swiper-next" type="button" aria-label="<?php esc_attr_e( 'Próxima marca', 'dv-visual' ); ?>"><span aria-hidden="true">→</span></button>
				</div>
			</div>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_clients_strip', 'diniz_studio_clients_strip_shortcode' );

/**
 * Dynamic service list for the dark ecosystem section.
 *
 * @return string
 */
function diniz_studio_service_list_shortcode() {
	$services = new WP_Query(
		array(
			'post_type'      => 'service',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);

	if ( ! $services->have_posts() ) {
		return current_user_can( 'edit_posts' ) ? '<p class="dv-dynamic-hint dv-dynamic-hint--dark">Cadastre soluções para preencher este ecossistema.</p>' : '';
	}

	ob_start();
	?>
	<div class="dv-service-list">
		<?php
		while ( $services->have_posts() ) :
			$services->the_post();
			$summary = diniz_studio_content_summary( get_the_ID() );
			?>
			<a href="<?php the_permalink(); ?>"><?php echo diniz_studio_service_icon( get_the_ID(), 'list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><strong><?php the_title(); ?></strong><small><?php echo esc_html( $summary ); ?></small><i>↗</i></a>
		<?php endwhile; ?>
	</div>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_service_list', 'diniz_studio_service_list_shortcode' );

/**
 * Dynamic testimonial carousel for the home page.
 *
 * @return string
 */
function diniz_studio_testimonials_shortcode() {
	$testimonials = new WP_Query(
		array(
			'post_type'      => 'testimonial',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);

	if ( ! $testimonials->have_posts() ) {
		return current_user_can( 'edit_posts' ) ? '<p class="dv-dynamic-hint dv-dynamic-hint--dark">Cadastre depoimentos para ativar o carrossel.</p>' : '';
	}

	ob_start();
	?>
	<section class="dv-testimonials swiper" aria-label="<?php esc_attr_e( 'Depoimentos de clientes', 'dv-visual' ); ?>">
		<div class="swiper-wrapper">
			<?php
			while ( $testimonials->have_posts() ) :
				$testimonials->the_post();
				$role    = diniz_studio_content_field( 'person_role', get_the_ID() );
				$company = diniz_studio_content_field( 'person_company', get_the_ID() );
				$rating  = max( 1, min( 5, (int) diniz_studio_content_field( 'rating', get_the_ID() ) ) );
				$quote   = get_the_content() ?: diniz_studio_content_summary( get_the_ID() );
				?>
				<blockquote class="swiper-slide">
					<span class="dv-testimonial-stars" aria-label="<?php echo esc_attr( sprintf( '%d de 5 estrelas', $rating ) ); ?>"><?php echo esc_html( str_repeat( '★', $rating ) ); ?></span>
					<p>“<?php echo esc_html( wp_strip_all_tags( $quote ) ); ?>”</p>
					<cite><strong><?php the_title(); ?></strong><?php if ( $role || $company ) : ?><span><?php echo esc_html( implode( ' · ', array_filter( array( $role, $company ) ) ) ); ?></span><?php endif; ?></cite>
				</blockquote>
			<?php endwhile; ?>
		</div>
		<div class="dv-testimonial-controls dv-swiper-controls">
			<button class="dv-swiper-button dv-swiper-prev" type="button" aria-label="<?php esc_attr_e( 'Depoimento anterior', 'dv-visual' ); ?>"><span aria-hidden="true">←</span></button>
			<div class="dv-swiper-pagination swiper-pagination"></div>
			<button class="dv-swiper-button dv-swiper-next" type="button" aria-label="<?php esc_attr_e( 'Próximo depoimento', 'dv-visual' ); ?>"><span aria-hidden="true">→</span></button>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_testimonials', 'diniz_studio_testimonials_shortcode' );

/**
 * Reusable featured collection for pages and the home.
 *
 * @param array $attributes Shortcode attributes.
 * @return string
 */
function diniz_studio_cpt_featured_shortcode( $attributes ) {
	$attributes = shortcode_atts(
		array(
			'post_type' => 'product',
			'limit'     => 3,
			'kicker'    => '',
			'title'     => '',
		),
		$attributes,
		'dv_cpt_featured'
	);

	$post_type = sanitize_key( $attributes['post_type'] );
	$configs   = diniz_studio_content_type_config();
	if ( ! isset( $configs[ $post_type ] ) ) {
		return '';
	}

	$config = $configs[ $post_type ];
	$limit  = max( 1, min( 8, (int) $attributes['limit'] ) );
	$query  = new WP_Query(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);

	if ( ! $query->have_posts() ) {
		return '';
	}

	ob_start();
	?>
	<section class="dv-cpt-featured dv-cpt-featured--<?php echo esc_attr( $post_type ); ?>">
		<div class="dv-cpt-featured__heading">
			<div><p class="dv-kicker"><?php echo esc_html( $attributes['kicker'] ?: $config['kicker'] ); ?></p><h2><?php echo esc_html( $attributes['title'] ?: $config['name'] ); ?></h2></div>
			<a href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>"><?php esc_html_e( 'Ver todos', 'dv-visual' ); ?> ↗</a>
		</div>
		<div class="dv-cpt-grid">
			<?php
			$index = 0;
			while ( $query->have_posts() ) :
				$query->the_post();
				$index++;
				echo diniz_studio_content_card( get_the_ID(), $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endwhile;
			?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_cpt_featured', 'diniz_studio_cpt_featured_shortcode' );

/**
 * Home section that connects products, tools and editorial guides.
 *
 * @return string
 */
function diniz_studio_content_hub_shortcode() {
	$types      = array( 'product', 'tool', 'guide' );
	$has_items  = false;
	$collections = array();

	foreach ( $types as $post_type ) {
		$collections[ $post_type ] = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 2,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			)
		);
		$has_items = $has_items || $collections[ $post_type ]->have_posts();
	}

	if ( ! $has_items ) {
		return '';
	}

	$configs = diniz_studio_content_type_config();
	ob_start();
	?>
	<section class="dv-content-hub">
		<div class="dv-content-hub__heading">
			<div><p class="dv-kicker"><?php esc_html_e( 'Explore, aprenda, avance', 'dv-visual' ); ?></p><h2><?php esc_html_e( 'Um ecossistema para sua marca continuar evoluindo.', 'dv-visual' ); ?></h2></div>
			<nav aria-label="<?php esc_attr_e( 'Áreas de conteúdo', 'dv-visual' ); ?>">
				<?php foreach ( $types as $post_type ) : ?><a href="<?php echo esc_url( get_post_type_archive_link( $post_type ) ); ?>"><?php echo esc_html( $configs[ $post_type ]['name'] ); ?></a><?php endforeach; ?>
			</nav>
		</div>
		<div class="dv-cpt-grid">
			<?php
			$index = 0;
			foreach ( $collections as $query ) :
				while ( $query->have_posts() ) :
					$query->the_post();
					$index++;
					echo diniz_studio_content_card( get_the_ID(), $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endwhile;
			endforeach;
			?>
		</div>
	</section>
	<?php
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'dv_content_hub', 'diniz_studio_content_hub_shortcode' );

function diniz_studio_content_body_classes( $classes ) {
	if ( is_singular() ) {
		$classes[] = 'dv-singular';
	}
	if ( is_post_type_archive() || is_archive() ) {
		$classes[] = 'dv-archive';
	}
	$post_type = get_post_type();
	if ( ! $post_type && is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
	}
	if ( $post_type && isset( diniz_studio_content_type_config()[ $post_type ] ) ) {
		$classes[] = 'dv-type-' . sanitize_html_class( $post_type );
	}
	return $classes;
}
add_filter( 'body_class', 'diniz_studio_content_body_classes' );

function diniz_studio_integrations_head() {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$ga4    = get_field( 'ga4_id', 'option' );
	$gtm    = get_field( 'gtm_id', 'option' );
	$pixel  = get_field( 'meta_pixel', 'option' );
	$custom = get_field( 'custom_head', 'option' );

	if ( $gtm && preg_match( '/^GTM-[A-Z0-9]+$/', $gtm ) ) {
		printf( '<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";j.async=true;j.src="https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f)})(window,document,"script","dataLayer","%s");</script>', esc_js( $gtm ) );
	}

	if ( $ga4 && preg_match( '/^G-[A-Z0-9]+$/', $ga4 ) ) {
		printf( '<script async src="https://www.googletagmanager.com/gtag/js?id=%1$s"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag("js",new Date());gtag("config","%1$s");</script>', esc_attr( $ga4 ) );
	}

	if ( $pixel && preg_match( '/^[0-9]+$/', $pixel ) ) {
		printf( '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init","%s");fbq("track","PageView");</script>', esc_js( $pixel ) );
	}

	if ( $custom ) {
		echo $custom; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_head', 'diniz_studio_integrations_head', 90 );

function diniz_studio_integrations_body_open() {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$gtm = get_field( 'gtm_id', 'option' );
	if ( $gtm && preg_match( '/^GTM-[A-Z0-9]+$/', $gtm ) ) {
		printf( '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>', esc_attr( $gtm ) );
	}
}
add_action( 'wp_body_open', 'diniz_studio_integrations_body_open', 1 );

function diniz_studio_integrations_footer() {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}

	$custom = get_field( 'custom_body', 'option' );
	if ( $custom ) {
		echo $custom; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'diniz_studio_integrations_footer', 90 );
