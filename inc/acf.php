<?php
/**
 * Advanced Custom Fields integration.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function diniz_studio_acf_json_save_point( $path ) {
	return get_template_directory() . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'diniz_studio_acf_json_save_point' );

function diniz_studio_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = get_template_directory() . '/acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'diniz_studio_acf_json_load_point' );

function diniz_studio_acf_options() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page( array(
		'page_title' => __( 'Configurações da DV Visual', 'dv-visual' ),
		'menu_title' => __( 'DV Visual', 'dv-visual' ),
		'menu_slug'  => 'dv-visual-settings',
		'capability' => 'edit_theme_options',
		'icon_url'   => 'dashicons-admin-customizer',
		'redirect'   => false,
	) );
	acf_add_options_sub_page( array(
		'page_title'  => __( 'Cabeçalho e Menus', 'dv-visual' ),
		'menu_title'  => __( 'Cabeçalho e Menus', 'dv-visual' ),
		'parent_slug' => 'dv-visual-settings',
	) );
	acf_add_options_sub_page( array(
		'page_title'  => __( 'Breadcrumbs do site', 'dv-visual' ),
		'menu_title'  => __( 'Breadcrumbs', 'dv-visual' ),
		'menu_slug'   => 'dv-visual-breadcrumbs',
		'parent_slug' => 'dv-visual-settings',
		'post_id'     => 'option',
	) );
	acf_add_options_sub_page( array(
		'page_title'  => __( 'Hero e Carrosséis', 'dv-visual' ),
		'menu_title'  => __( 'Hero e Carrosséis', 'dv-visual' ),
		'menu_slug'   => 'dv-visual-hero-carousels',
		'parent_slug' => 'dv-visual-settings',
		'post_id'     => 'option',
	) );
	acf_add_options_sub_page( array(
		'page_title'  => __( 'Rodapé e Contato', 'dv-visual' ),
		'menu_title'  => __( 'Rodapé e Contato', 'dv-visual' ),
		'parent_slug' => 'dv-visual-settings',
	) );
	acf_add_options_sub_page( array(
		'page_title'  => __( 'Scripts e Integrações', 'dv-visual' ),
		'menu_title'  => __( 'Scripts e Integrações', 'dv-visual' ),
		'parent_slug' => 'dv-visual-settings',
	) );
}
add_action( 'acf/init', 'diniz_studio_acf_options' );

/**
 * Keep the global breadcrumb controls available immediately.
 *
 * Local JSON remains the source for new installations. Registering each field
 * by key also prevents an older database copy of the global group from hiding
 * the new controls until an administrator manually synchronizes ACF.
 *
 * @return void
 */
function diniz_studio_register_breadcrumb_fields() {
	if ( ! function_exists( 'acf_add_local_field' ) ) {
		return;
	}

	$fields = array(
		array(
			'key'       => 'field_dv_breadcrumb_tab',
			'label'     => __( 'Breadcrumbs', 'dv-visual' ),
			'type'      => 'tab',
			'placement' => 'top',
			'parent'    => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_enabled',
			'label'         => __( 'Exibir breadcrumbs no site', 'dv-visual' ),
			'name'          => 'breadcrumb_enabled',
			'type'          => 'true_false',
			'instructions'  => __( 'Controla o componente em páginas, blog, categorias, buscas, arquivos e conteúdos individuais.', 'dv-visual' ),
			'default_value' => 1,
			'ui'            => 1,
			'parent'        => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_home_label',
			'label'         => __( 'Nome do início', 'dv-visual' ),
			'name'          => 'breadcrumb_home_label',
			'type'          => 'text',
			'default_value' => __( 'Início', 'dv-visual' ),
			'maxlength'     => 30,
			'parent'        => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_blog_label',
			'label'         => __( 'Nome do blog', 'dv-visual' ),
			'name'          => 'breadcrumb_blog_label',
			'type'          => 'text',
			'default_value' => __( 'Blog', 'dv-visual' ),
			'maxlength'     => 30,
			'parent'        => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_separator',
			'label'         => __( 'Separador', 'dv-visual' ),
			'name'          => 'breadcrumb_separator',
			'type'          => 'text',
			'instructions'  => __( 'Exemplos: ›, / ou →', 'dv-visual' ),
			'default_value' => '›',
			'maxlength'     => 3,
			'wrapper'       => array( 'width' => 25 ),
			'parent'        => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_style',
			'label'         => __( 'Estilo visual', 'dv-visual' ),
			'name'          => 'breadcrumb_style',
			'type'          => 'select',
			'choices'       => array(
				'minimal' => __( 'Minimalista', 'dv-visual' ),
				'glass'   => __( 'Vidro', 'dv-visual' ),
				'solid'   => __( 'Sólido', 'dv-visual' ),
			),
			'default_value' => 'minimal',
			'ui'            => 1,
			'wrapper'       => array( 'width' => 35 ),
			'parent'        => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_show_current',
			'label'         => __( 'Mostrar página atual', 'dv-visual' ),
			'name'          => 'breadcrumb_show_current',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'wrapper'       => array( 'width' => 40 ),
			'parent'        => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_accent_color',
			'label'         => __( 'Cor de destaque', 'dv-visual' ),
			'name'          => 'breadcrumb_accent_color',
			'type'          => 'color_picker',
			'default_value' => '#14B8B5',
			'return_format' => 'string',
			'wrapper'       => array( 'width' => 50 ),
			'parent'        => 'group_diniz_theme',
		),
		array(
			'key'           => 'field_dv_breadcrumb_background_color',
			'label'         => __( 'Cor de fundo', 'dv-visual' ),
			'name'          => 'breadcrumb_background_color',
			'type'          => 'color_picker',
			'instructions'  => __( 'Aplicada nos estilos Vidro e Sólido.', 'dv-visual' ),
			'default_value' => '#FFFFFF',
			'return_format' => 'string',
			'wrapper'       => array( 'width' => 50 ),
			'parent'        => 'group_diniz_theme',
		),
	);

	foreach ( $fields as $field ) {
		acf_add_local_field( $field );
	}
}
add_action( 'acf/init', 'diniz_studio_register_breadcrumb_fields', 19 );

/**
 * Keep the two Portfolio card controls available on existing installations.
 *
 * ACF Local JSON marks updated groups for manual synchronization when a group
 * already exists in the database. Registering these fields by key makes the
 * new controls available immediately while the bundled JSON remains the source
 * used by fresh installations.
 */
function diniz_studio_register_portfolio_card_fields() {
	if ( ! function_exists( 'acf_add_local_field' ) ) {
		return;
	}

	acf_add_local_field(
		array(
			'key'          => 'field_dv_project_card_badge',
			'label'        => __( 'Selo do card', 'dv-visual' ),
			'name'         => 'project_card_badge',
			'type'         => 'text',
			'instructions' => __( 'Texto pequeno exibido acima do título nos cards. Ex.: Projeto selecionado, Branding ou Case em destaque.', 'dv-visual' ),
			'placeholder'  => __( 'Projeto selecionado', 'dv-visual' ),
			'maxlength'    => 60,
			'parent'       => 'group_dv_portfolio',
		)
	);

	acf_add_local_field(
		array(
			'key'          => 'field_dv_project_card_description',
			'label'        => __( 'Descrição curta do card', 'dv-visual' ),
			'name'         => 'project_card_description',
			'type'         => 'text',
			'instructions' => __( 'Resumo exibido abaixo do título. Se ficar vazio, o card usa o Escopo do projeto.', 'dv-visual' ),
			'placeholder'  => __( 'Estratégia, design e experiência digital', 'dv-visual' ),
			'maxlength'    => 140,
			'parent'       => 'group_dv_portfolio',
		)
	);
}
add_action( 'acf/init', 'diniz_studio_register_portfolio_card_fields', 20 );

/**
 * Make the Portfolio Scroll container fields available immediately.
 *
 * Existing sites may keep an older database copy of the Portfolio field group,
 * so these fields are registered by key in addition to the bundled Local JSON.
 *
 * @return void
 */
function diniz_studio_register_portfolio_scroll_fields() {
	if ( ! function_exists( 'acf_add_local_field' ) ) {
		return;
	}

	$fields = array(
		array(
			'key'       => 'field_dv_project_scroll_tab',
			'label'     => __( 'Scroll container', 'dv-visual' ),
			'type'      => 'tab',
			'placement' => 'top',
			'parent'    => 'group_dv_portfolio',
		),
		array(
			'key'           => 'field_dv_project_scroll_kicker',
			'label'         => __( 'Chamada pequena', 'dv-visual' ),
			'name'          => 'project_scroll_kicker',
			'type'          => 'text',
			'default_value' => __( 'Apresentação completa', 'dv-visual' ),
			'parent'        => 'group_dv_portfolio',
		),
		array(
			'key'           => 'field_dv_project_scroll_title',
			'label'         => __( 'Título do Scroll container', 'dv-visual' ),
			'name'          => 'project_scroll_title',
			'type'          => 'text',
			'default_value' => __( 'Explore o projeto em detalhes.', 'dv-visual' ),
			'parent'        => 'group_dv_portfolio',
		),
		array(
			'key'       => 'field_dv_project_scroll_text',
			'label'     => __( 'Texto de apoio', 'dv-visual' ),
			'name'      => 'project_scroll_text',
			'type'      => 'textarea',
			'rows'      => 2,
			'new_lines' => 'br',
			'parent'    => 'group_dv_portfolio',
		),
		array(
			'key'          => 'field_dv_project_scroll_items',
			'label'        => __( 'Arquivos do Scroll container', 'dv-visual' ),
			'name'         => 'project_scroll_items',
			'type'         => 'repeater',
			'instructions' => __( 'Adicione imagens ou PDFs. Sem galeria, este bloco ocupa o lugar dela; com galeria, aparece logo abaixo.', 'dv-visual' ),
			'layout'       => 'block',
			'button_label' => __( 'Adicionar imagem ou PDF', 'dv-visual' ),
			'collapsed'    => 'field_dv_project_scroll_item_title',
			'parent'       => 'group_dv_portfolio',
		),
		array(
			'key'           => 'field_dv_project_scroll_item_media',
			'label'         => __( 'Imagem ou PDF', 'dv-visual' ),
			'name'          => 'media',
			'type'          => 'file',
			'required'      => 1,
			'return_format' => 'array',
			'library'       => 'all',
			'mime_types'    => 'jpg,jpeg,png,gif,webp,avif,svg,pdf',
			'parent'        => 'field_dv_project_scroll_items',
		),
		array(
			'key'    => 'field_dv_project_scroll_item_title',
			'label'  => __( 'Título do arquivo', 'dv-visual' ),
			'name'   => 'title',
			'type'   => 'text',
			'parent' => 'field_dv_project_scroll_items',
		),
		array(
			'key'       => 'field_dv_project_scroll_item_caption',
			'label'     => __( 'Legenda', 'dv-visual' ),
			'name'      => 'caption',
			'type'      => 'textarea',
			'rows'      => 2,
			'new_lines' => 'br',
			'parent'    => 'field_dv_project_scroll_items',
		),
	);

	foreach ( $fields as $field ) {
		acf_add_local_field( $field );
	}
}
add_action( 'acf/init', 'diniz_studio_register_portfolio_scroll_fields', 22 );

/**
 * Keep the Software carousel controls available immediately when an existing
 * database still contains an older copy of the Hero and carousels field group.
 */
function diniz_studio_register_software_carousel_fields() {
	if ( ! function_exists( 'acf_add_local_field' ) ) {
		return;
	}

	$fields = array(
		array(
			'key'    => 'field_dv_software_tab',
			'label'  => __( 'Softwares utilizados', 'dv-visual' ),
			'type'   => 'tab',
			'parent' => 'group_dv_home_carousels',
		),
		array(
			'key'           => 'field_dv_software_kicker',
			'label'         => __( 'Selo da seção', 'dv-visual' ),
			'name'          => 'dv_software_kicker',
			'type'          => 'text',
			'default_value' => __( 'Tecnologia no processo', 'dv-visual' ),
			'parent'        => 'group_dv_home_carousels',
		),
		array(
			'key'           => 'field_dv_software_title',
			'label'         => __( 'Título da seção', 'dv-visual' ),
			'name'          => 'dv_software_title',
			'type'          => 'text',
			'default_value' => __( 'Softwares que usamos todos os dias.', 'dv-visual' ),
			'parent'        => 'group_dv_home_carousels',
		),
		array(
			'key'           => 'field_dv_software_text',
			'label'         => __( 'Texto de apoio', 'dv-visual' ),
			'name'          => 'dv_software_text',
			'type'          => 'textarea',
			'rows'          => 2,
			'maxlength'     => 180,
			'default_value' => __( 'Ferramentas profissionais escolhidas para dar precisão, consistência e agilidade a cada entrega.', 'dv-visual' ),
			'parent'        => 'group_dv_home_carousels',
		),
		array(
			'key'           => 'field_dv_software_autoplay',
			'label'         => __( 'Troca automática dos softwares', 'dv-visual' ),
			'name'          => 'dv_software_autoplay',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'parent'        => 'group_dv_home_carousels',
		),
		array(
			'key'           => 'field_dv_software_delay',
			'label'         => __( 'Intervalo do carrossel de softwares', 'dv-visual' ),
			'name'          => 'dv_software_delay',
			'type'          => 'number',
			'default_value' => 3000,
			'min'           => 1600,
			'max'           => 12000,
			'step'          => 100,
			'append'        => 'ms',
			'parent'        => 'group_dv_home_carousels',
		),
	);

	foreach ( $fields as $field ) {
		acf_add_local_field( $field );
	}
}
add_action( 'acf/init', 'diniz_studio_register_software_carousel_fields', 21 );

function diniz_studio_acf_notice() {
	if ( current_user_can( 'activate_plugins' ) && ! function_exists( 'get_field' ) ) {
		echo '<div class="notice notice-info"><p><strong>DV Visual:</strong> instale e ative o Advanced Custom Fields PRO para utilizar os campos personalizados e o conteúdo flexível incluídos no tema.</p></div>';
	}
}
add_action( 'admin_notices', 'diniz_studio_acf_notice' );
