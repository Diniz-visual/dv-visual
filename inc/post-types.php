<?php
/**
 * Theme content types.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central content-type definitions used by registration and front-end views.
 *
 * @return array<string,array<string,mixed>>
 */
function diniz_studio_content_type_config() {
	return array(
		'portfolio' => array(
			'name'        => 'Portfólio',
			'singular'    => 'Projeto',
			'icon'        => 'dashicons-portfolio',
			'rewrite'     => 'portfolio',
			'kicker'      => 'Cases selecionados',
			'description' => 'Projetos de branding, identidade visual, sites e produtos digitais criados para fortalecer marcas e gerar resultados.',
			'cta'         => 'Ver projeto',
		),
		'product' => array(
			'name'        => 'Produtos',
			'singular'    => 'Produto',
			'icon'        => 'dashicons-screenoptions',
			'rewrite'     => 'produto',
			'kicker'      => 'Produtos digitais',
			'description' => 'Produtos digitais pensados para simplificar jornadas, fortalecer a presença online e acelerar resultados.',
			'cta'         => 'Conhecer produto',
		),
		'service' => array(
			'name'        => 'Soluções',
			'singular'    => 'Solução',
			'icon'        => 'dashicons-admin-tools',
			'rewrite'     => 'solucao',
			'kicker'      => 'Como podemos ajudar',
			'description' => 'Soluções de branding, identidade visual, design, conteúdo e tecnologia para marcas que querem crescer com clareza.',
			'cta'         => 'Ver solução',
		),
		'tool' => array(
			'name'        => 'Ferramentas',
			'singular'    => 'Ferramenta',
			'icon'        => 'dashicons-hammer',
			'rewrite'     => 'ferramenta',
			'kicker'      => 'Recursos práticos',
			'description' => 'Ferramentas, modelos e recursos práticos para planejar melhor, tomar decisões e transformar estratégia em ação.',
			'cta'         => 'Acessar ferramenta',
		),
		'software' => array(
			'name'        => 'Softwares',
			'singular'    => 'Software',
			'icon'        => 'dashicons-desktop',
			'rewrite'     => 'software',
			'kicker'      => 'Tecnologia no processo',
			'description' => 'Softwares profissionais usados no planejamento, criação, desenvolvimento e entrega dos projetos.',
			'cta'         => 'Conhecer software',
		),
		'guide' => array(
			'name'        => 'Guias',
			'singular'    => 'Guia',
			'icon'        => 'dashicons-welcome-learn-more',
			'rewrite'     => 'guia',
			'kicker'      => 'Conhecimento aplicado',
			'description' => 'Guias completos sobre branding, comunicação, design e experiência digital para orientar decisões mais seguras.',
			'cta'         => 'Ler guia',
		),
		'help_article' => array(
			'name'        => 'Central de Ajuda',
			'singular'    => 'Artigo de ajuda',
			'icon'        => 'dashicons-editor-help',
			'rewrite'     => 'ajuda',
			'kicker'      => 'Suporte e orientação',
			'description' => 'Respostas objetivas, orientações e documentação para encontrar informações e aproveitar melhor cada entrega.',
			'cta'         => 'Ver resposta',
		),
		'job' => array(
			'name'        => 'Vagas',
			'singular'    => 'Vaga',
			'icon'        => 'dashicons-businessperson',
			'rewrite'     => 'vaga',
			'kicker'      => 'Trabalhe com a gente',
			'description' => 'Oportunidades para pessoas curiosas, colaborativas e apaixonadas por construir coisas relevantes.',
			'cta'         => 'Ver oportunidade',
		),
		'testimonial' => array(
			'name'        => 'Depoimentos',
			'singular'    => 'Depoimento',
			'icon'        => 'dashicons-format-quote',
			'rewrite'     => 'depoimento',
			'kicker'      => 'Experiências reais',
			'description' => 'O que clientes e parceiros contam sobre criar, lançar e evoluir projetos com nosso estúdio.',
			'cta'         => 'Ler relato',
		),
		'team' => array(
			'name'        => 'Equipe',
			'singular'    => 'Pessoa',
			'icon'        => 'dashicons-groups',
			'rewrite'     => 'equipe',
			'kicker'      => 'Nosso time',
			'description' => 'Um time multidisciplinar que une pensamento estratégico, repertório e execução cuidadosa.',
			'cta'         => 'Conhecer perfil',
		),
		'client' => array(
			'name'        => 'Clientes',
			'singular'    => 'Cliente',
			'icon'        => 'dashicons-businesswoman',
			'rewrite'     => 'cliente',
			'kicker'      => 'Marcas parceiras',
			'description' => 'Empresas que confiaram em nosso trabalho para tornar sua presença mais clara, relevante e memorável.',
			'cta'         => 'Conhecer parceria',
		),
		'award' => array(
			'name'        => 'Prêmios',
			'singular'    => 'Prêmio',
			'icon'        => 'dashicons-awards',
			'rewrite'     => 'premio',
			'kicker'      => 'Reconhecimento',
			'description' => 'Seleções, destaques e conquistas que celebram a qualidade e o impacto do nosso trabalho.',
			'cta'         => 'Ver reconhecimento',
		),
	);
}

function diniz_studio_register_content_types() {
	$types = diniz_studio_content_type_config();

	foreach ( $types as $slug => $data ) {
		$is_enabled = ! function_exists( 'diniz_studio_is_home_content_type_enabled' ) || diniz_studio_is_home_content_type_enabled( $slug );

		register_post_type( $slug, array(
			'labels' => array(
				'name'               => $data['name'],
				'singular_name'      => $data['singular'],
				'add_new'            => 'Adicionar novo',
				'add_new_item'       => sprintf( 'Adicionar %s', $data['singular'] ),
				'edit_item'          => sprintf( 'Editar %s', $data['singular'] ),
				'new_item'           => sprintf( 'Novo %s', $data['singular'] ),
				'view_item'          => sprintf( 'Ver %s', $data['singular'] ),
				'view_items'         => sprintf( 'Ver %s', $data['name'] ),
				'search_items'       => sprintf( 'Buscar em %s', $data['name'] ),
				'not_found'          => 'Nenhum conteúdo encontrado.',
				'not_found_in_trash' => 'Nenhum conteúdo encontrado na lixeira.',
				'all_items'          => $data['name'],
				'archives'           => sprintf( 'Arquivo de %s', $data['name'] ),
			),
			'public'          => true,
			'show_in_menu'    => true,
			'show_in_admin_bar' => $is_enabled,
			'show_in_rest'    => true,
			'show_in_nav_menus' => $is_enabled,
			'has_archive'     => true,
			'rewrite'         => array(
				'slug'       => $data['rewrite'],
				'with_front' => false,
			),
			'menu_icon'       => $data['icon'],
			'menu_position'   => 20,
			'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
		) );
	}

	register_post_type( 'faq', array(
		'labels' => array(
			'name'               => 'Perguntas Frequentes',
			'singular_name'      => 'Pergunta frequente',
			'menu_name'          => 'Perguntas Frequentes',
			'add_new'            => 'Adicionar pergunta',
			'add_new_item'       => 'Adicionar pergunta frequente',
			'edit_item'          => 'Editar pergunta',
			'new_item'           => 'Nova pergunta',
			'view_item'          => 'Ver pergunta',
			'search_items'       => 'Buscar perguntas',
			'not_found'          => 'Nenhuma pergunta encontrada.',
			'not_found_in_trash' => 'Nenhuma pergunta encontrada na lixeira.',
			'all_items'          => 'Todas as perguntas',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'show_in_rest'        => true,
		'show_in_nav_menus'   => false,
		'has_archive'         => false,
		'menu_icon'           => 'dashicons-editor-help',
		'menu_position'       => 20,
		'supports'            => array( 'title', 'editor', 'revisions', 'custom-fields', 'page-attributes' ),
	) );

	register_taxonomy( 'solution_category', array( 'portfolio', 'product', 'service', 'tool' ), array(
		'labels'       => array( 'name' => 'Categorias de solução', 'singular_name' => 'Categoria de solução' ),
		'public'       => true,
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'categoria-solucao' ),
	) );

	register_taxonomy( 'project_sector', array( 'portfolio', 'client' ), array(
		'labels'       => array( 'name' => 'Segmentos', 'singular_name' => 'Segmento' ),
		'public'       => true,
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'segmento' ),
	) );

	register_taxonomy( 'project_skill', array( 'portfolio', 'service', 'team' ), array(
		'labels'       => array( 'name' => 'Especialidades', 'singular_name' => 'Especialidade' ),
		'public'       => true,
		'hierarchical' => false,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'especialidade' ),
	) );

	register_taxonomy( 'guide_category', array( 'guide', 'help_article' ), array(
		'labels'       => array( 'name' => 'Categorias de conteúdo', 'singular_name' => 'Categoria de conteúdo' ),
		'public'       => true,
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'categoria-conteudo' ),
	) );

	register_taxonomy( 'job_area', array( 'job' ), array(
		'labels'       => array( 'name' => 'Áreas das vagas', 'singular_name' => 'Área da vaga' ),
		'public'       => true,
		'hierarchical' => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'area' ),
	) );
}
add_action( 'init', 'diniz_studio_register_content_types' );

/**
 * Seed the original FAQ content into the FAQ Custom Post Type once.
 * Existing FAQ content is never overwritten.
 *
 * @return void
 */
function diniz_studio_maybe_seed_faq_content() {
	if ( get_option( 'diniz_studio_faq_seeded' ) ) {
		return;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'faq',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( ! $existing ) {
		$items = array(
			array(
				'question' => 'Quanto tempo até começar a ver resultados?',
				'answer'   => 'O prazo varia por estratégia. Sites podem gerar impacto imediato; SEO amadurece progressivamente.',
			),
			array(
				'question' => 'Os serviços podem ser contratados separadamente?',
				'answer'   => 'Sim. O plano pode começar por uma necessidade específica e evoluir de forma integrada.',
			),
			array(
				'question' => 'Vocês atendem qualquer segmento?',
				'answer'   => 'O diagnóstico inicial confirma aderência, metas e o melhor formato de trabalho.',
			),
			array(
				'question' => 'Como o desempenho é acompanhado?',
				'answer'   => 'Por indicadores definidos com você e relatórios claros, conectados aos objetivos comerciais.',
			),
		);

		foreach ( $items as $order => $item ) {
			wp_insert_post(
				array(
					'post_type'    => 'faq',
					'post_status'  => 'publish',
					'post_title'   => $item['question'],
					'post_content' => $item['answer'],
					'menu_order'   => $order,
				)
			);
		}
	}

	update_option( 'diniz_studio_faq_seeded', 1, false );
}
add_action( 'admin_init', 'diniz_studio_maybe_seed_faq_content', 20 );

/**
 * Keep the Solução icon field directly below the featured image in the
 * classic WordPress editor sidebar. ACF already places the same field in the
 * document sidebar when the block editor is active.
 *
 * @return void
 */
function diniz_studio_place_service_icon_below_thumbnail() {
	$screen = get_current_screen();

	if ( ! $screen || 'service' !== $screen->post_type ) {
		return;
	}
	?>
	<script>
	window.addEventListener('load', function () {
		var featuredImage = document.getElementById('postimagediv');
		var serviceIcon = document.getElementById('acf-group_dv_service_icon');

		if (featuredImage && serviceIcon && featuredImage.parentNode) {
			featuredImage.insertAdjacentElement('afterend', serviceIcon);
		}
	});
	</script>
	<?php
}
add_action( 'admin_footer-post.php', 'diniz_studio_place_service_icon_below_thumbnail' );
add_action( 'admin_footer-post-new.php', 'diniz_studio_place_service_icon_below_thumbnail' );

/**
 * Add an editable starter set so the new Home carousel is useful immediately.
 * Every item can be renamed, reordered, unpublished or deleted in Softwares.
 *
 * @return void
 */
function diniz_studio_seed_software_content() {
	$existing = get_posts(
		array(
			'post_type'      => 'software',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( $existing ) {
		return;
	}

	$softwares = array(
		'Figma'             => 'Interfaces, protótipos e sistemas visuais colaborativos.',
		'Adobe Photoshop'   => 'Tratamento de imagens, composição e acabamento visual.',
		'Adobe Illustrator' => 'Identidades, ilustrações e ativos vetoriais escaláveis.',
		'WordPress'         => 'Sites gerenciáveis, conteúdo e experiências digitais.',
		'After Effects'     => 'Motion design, vinhetas e animações de marca.',
		'Premiere Pro'      => 'Edição e finalização de conteúdo audiovisual.',
	);

	$order = 0;
	foreach ( $softwares as $title => $summary ) {
		$post_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'software',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => $summary,
					'menu_order'   => $order,
				)
			)
		);

		if ( ! is_wp_error( $post_id ) && $post_id ) {
			update_post_meta( $post_id, '_content_summary', 'field_dv_software_summary' );
			update_post_meta( $post_id, 'content_summary', $summary );
		}
		$order++;
	}
}

/**
 * Seed the carousel once on existing installations after the theme update.
 *
 * @return void
 */
function diniz_studio_maybe_seed_software_content() {
	if ( '1.0.0' === get_option( 'diniz_studio_software_content_version' ) ) {
		return;
	}

	diniz_studio_seed_software_content();
	update_option( 'diniz_studio_software_content_version', '1.0.0', false );
}
add_action( 'init', 'diniz_studio_maybe_seed_software_content', 15 );

/**
 * Register the new /software/ URLs once without asking the user to resave the
 * WordPress permalink settings.
 *
 * @return void
 */
function diniz_studio_maybe_flush_software_rewrites() {
	if ( '1.0.0' === get_option( 'diniz_studio_software_rewrite_version' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'diniz_studio_software_rewrite_version', '1.0.0', false );
}
add_action( 'admin_init', 'diniz_studio_maybe_flush_software_rewrites', 20 );
