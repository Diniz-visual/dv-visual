<?php
/** Editable page labels, reusable section copy and native SEO fallbacks. @package DinizStudio */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function diniz_studio_editable_value( $name, $post_id = 0, $default = '' ) {
	$value = '';
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $name, $post_id ?: false );
	} elseif ( is_numeric( $post_id ) && $post_id ) {
		$value = get_post_meta( (int) $post_id, $name, true );
	}
	return ( '' === $value || null === $value || false === $value ) ? $default : $value;
}
function diniz_studio_page_text( $name, $default = '', $post_id = 0 ) {
	$post_id = $post_id ?: get_queried_object_id();
	$value = diniz_studio_editable_value( $name, $post_id, $default );
	return is_string( $value ) ? $value : $default;
}
function diniz_studio_global_text( $name, $default = '' ) {
	$value = diniz_studio_editable_value( $name, 'option', $default );
	return is_string( $value ) ? $value : $default;
}
function diniz_studio_editable_content_options_page() {
	if ( ! function_exists( 'acf_add_options_sub_page' ) ) { return; }
	acf_add_options_sub_page( array(
		'page_title' => __( 'Textos do site', 'dv-visual' ),
		'menu_title' => __( 'Textos do site', 'dv-visual' ),
		'menu_slug' => 'dv-visual-site-copy',
		'parent_slug' => 'dv-visual-settings',
		'post_id' => 'option',
	) );
}
add_action( 'acf/init', 'diniz_studio_editable_content_options_page', 12 );

function diniz_studio_register_page_copy_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	$fields = array(
		array( 'key'=>'field_dv_page_copy_tab_hero','label'=>__( 'Cabeçalho da página','dv-visual' ),'type'=>'tab','placement'=>'top' ),
		array( 'key'=>'field_dv_page_kicker','label'=>__( 'Sobretítulo / kicker','dv-visual' ),'name'=>'dv_page_kicker','type'=>'text','instructions'=>__( 'Substitui o pequeno texto acima do título quando o template possuir esse elemento.','dv-visual' ) ),
		array( 'key'=>'field_dv_page_lead','label'=>__( 'Texto de apoio','dv-visual' ),'name'=>'dv_page_lead','type'=>'textarea','rows'=>3 ),
		array( 'key'=>'field_dv_page_copy_tab_sections','label'=>__( 'Títulos das seções','dv-visual' ),'type'=>'tab','placement'=>'top' ),
	);
	for ( $i=1; $i<=6; $i++ ) {
		$fields[] = array( 'key'=>'field_dv_page_section_'.$i.'_kicker','label'=>sprintf( __( 'Seção %d — Sobretítulo','dv-visual' ),$i ),'name'=>'dv_page_section_'.$i.'_kicker','type'=>'text','wrapper'=>array('width'=>35) );
		$fields[] = array( 'key'=>'field_dv_page_section_'.$i.'_title','label'=>sprintf( __( 'Seção %d — Título','dv-visual' ),$i ),'name'=>'dv_page_section_'.$i.'_title','type'=>'text','wrapper'=>array('width'=>65) );
		$fields[] = array( 'key'=>'field_dv_page_section_'.$i.'_text','label'=>sprintf( __( 'Seção %d — Texto de apoio','dv-visual' ),$i ),'name'=>'dv_page_section_'.$i.'_text','type'=>'textarea','rows'=>2 );
	}
	acf_add_local_field_group( array(
		'key'=>'group_dv_page_editable_copy','title'=>__( 'DV Visual — Textos e títulos da página','dv-visual' ),'fields'=>$fields,
		'location'=>array( array( array('param'=>'post_type','operator'=>'==','value'=>'page') ) ),
		'menu_order'=>8,'position'=>'normal','style'=>'default','active'=>true,
	) );
}
add_action( 'acf/init', 'diniz_studio_register_page_copy_fields', 24 );

function diniz_studio_register_global_copy_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	$f = array(
		array('key'=>'field_dv_global_process_tab','label'=>'Processo','type'=>'tab'),
		array('key'=>'field_dv_process_kicker','label'=>'Sobretítulo','name'=>'dv_process_kicker','type'=>'text','default_value'=>'Método'),
		array('key'=>'field_dv_process_title','label'=>'Título','name'=>'dv_process_title','type'=>'text','default_value'=>'Um processo claro, do diagnóstico ao resultado.'),
		array('key'=>'field_dv_global_faq_tab','label'=>'FAQ','type'=>'tab'),
		array('key'=>'field_dv_faq_kicker','label'=>'Sobretítulo','name'=>'dv_faq_kicker','type'=>'text','default_value'=>'Dúvidas frequentes'),
		array('key'=>'field_dv_faq_title','label'=>'Título','name'=>'dv_faq_title','type'=>'text','default_value'=>'Antes de começar.'),
		array('key'=>'field_dv_faq_text','label'=>'Texto','name'=>'dv_faq_text','type'=>'textarea','rows'=>2,'default_value'=>'Respostas objetivas para as perguntas mais comuns.'),
		array('key'=>'field_dv_global_cta_tab','label'=>'CTA global','type'=>'tab'),
		array('key'=>'field_dv_cta_kicker','label'=>'Sobretítulo','name'=>'dv_cta_kicker','type'=>'text','default_value'=>'Seu próximo capítulo começa aqui'),
		array('key'=>'field_dv_cta_title','label'=>'Título','name'=>'dv_cta_title','type'=>'textarea','rows'=>2,'default_value'=>'Vamos criar uma marca que ninguém confunde?'),
		array('key'=>'field_dv_cta_text','label'=>'Texto','name'=>'dv_cta_text','type'=>'textarea','rows'=>2,'default_value'=>'Conte sobre o seu momento. A primeira conversa é simples, estratégica e sem compromisso.'),
		array('key'=>'field_dv_cta_link','label'=>'Botão','name'=>'dv_cta_link','type'=>'link'),
		array('key'=>'field_dv_global_blog_tab','label'=>'Blog','type'=>'tab'),
		array('key'=>'field_dv_blog_filter_kicker','label'=>'Filtro — Sobretítulo','name'=>'dv_blog_filter_kicker','type'=>'text','default_value'=>'Explore por assunto'),
		array('key'=>'field_dv_blog_filter_title','label'=>'Filtro — Título','name'=>'dv_blog_filter_title','type'=>'text','default_value'=>'Encontre o conteúdo certo para agora.'),
		array('key'=>'field_dv_blog_categories_kicker','label'=>'Categorias — Sobretítulo','name'=>'dv_blog_categories_kicker','type'=>'text','default_value'=>'Navegue por assunto'),
		array('key'=>'field_dv_blog_categories_title','label'=>'Categorias — Título','name'=>'dv_blog_categories_title','type'=>'text','default_value'=>'Categorias'),
		array('key'=>'field_dv_global_portfolio_tab','label'=>'Portfólio','type'=>'tab'),
		array('key'=>'field_dv_portfolio_home_kicker','label'=>'Home — Sobretítulo','name'=>'dv_portfolio_home_kicker','type'=>'text','default_value'=>'Projetos selecionados'),
		array('key'=>'field_dv_portfolio_home_title','label'=>'Home — Título','name'=>'dv_portfolio_home_title','type'=>'text','default_value'=>'Trabalho que fala por si.'),
		array('key'=>'field_dv_global_footer_tab','label'=>'Rodapé','type'=>'tab'),
		array('key'=>'field_dv_footer_solutions_title','label'=>'Coluna Soluções','name'=>'dv_footer_solutions_title','type'=>'text','default_value'=>'Soluções'),
		array('key'=>'field_dv_footer_content_title','label'=>'Coluna Blog','name'=>'dv_footer_content_title','type'=>'text','default_value'=>'Blog & conteúdo'),
		array('key'=>'field_dv_footer_guides_title','label'=>'Coluna Guias','name'=>'dv_footer_guides_title','type'=>'text','default_value'=>'Guias completos'),
		array('key'=>'field_dv_footer_institutional_title','label'=>'Coluna Institucional','name'=>'dv_footer_institutional_title','type'=>'text','default_value'=>'Institucional'),
		array('key'=>'field_dv_global_404_tab','label'=>'Página 404','type'=>'tab'),
		array('key'=>'field_dv_404_kicker','label'=>'Sobretítulo','name'=>'dv_404_kicker','type'=>'text','default_value'=>'Rota não encontrada'),
		array('key'=>'field_dv_404_title','label'=>'Título','name'=>'dv_404_title','type'=>'text','default_value'=>'Essa página se perdeu pelo caminho.'),
	);
	acf_add_local_field_group( array(
		'key'=>'group_dv_global_editable_copy','title'=>__( 'DV Visual — Textos globais','dv-visual' ),'fields'=>$f,
		'location'=>array( array( array('param'=>'options_page','operator'=>'==','value'=>'dv-visual-site-copy') ) ),'active'=>true,
	) );
}
add_action( 'acf/init', 'diniz_studio_register_global_copy_fields', 25 );

function diniz_studio_expand_hero_seo_locations( $group ) {
	$locations=array(); $post_types=get_post_types(array('public'=>true),'names'); unset($post_types['attachment']);
	foreach($post_types as $post_type){ $locations[]=array(array('param'=>'post_type','operator'=>'==','value'=>$post_type)); }
	$group['location']=$locations; return $group;
}
add_filter( 'acf/load_field_group/key=group_diniz_page_hero', 'diniz_studio_expand_hero_seo_locations' );

function diniz_studio_has_external_seo_plugin() {
	return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION');
}
function diniz_studio_document_title_from_seo( $title ) {
	if ( ! diniz_studio_has_external_seo_plugin() && is_singular() ) {
		$seo=trim((string)diniz_studio_editable_value('seo_title',get_queried_object_id(),'')); if($seo){return $seo;}
	}
	return $title;
}
add_filter('pre_get_document_title','diniz_studio_document_title_from_seo',20);
function diniz_studio_native_seo_meta() {
	if ( diniz_studio_has_external_seo_plugin() || ! is_singular() ) { return; }
	$post_id=get_queried_object_id();
	$title=trim((string)diniz_studio_editable_value('seo_title',$post_id,get_the_title($post_id)));
	$desc=trim((string)diniz_studio_editable_value('seo_description',$post_id,''));
	if(!$desc){ $desc=has_excerpt($post_id)?get_the_excerpt($post_id):wp_trim_words(wp_strip_all_tags(get_post_field('post_content',$post_id)),28,''); }
	$url=get_permalink($post_id); $og=diniz_studio_editable_value('og_image',$post_id,''); $img='';
	if(is_array($og)&&!empty($og['url'])){$img=$og['url'];} elseif(is_numeric($og)){$img=wp_get_attachment_image_url((int)$og,'full');} elseif(has_post_thumbnail($post_id)){$img=get_the_post_thumbnail_url($post_id,'full');}
	if($desc){echo '<meta name="description" content="'.esc_attr($desc).'">'."\n";}
	echo '<link rel="canonical" href="'.esc_url($url).'">'."\n";
	echo '<meta property="og:type" content="'.esc_attr(is_singular('post')?'article':'website').'">'."\n";
	echo '<meta property="og:title" content="'.esc_attr($title).'">'."\n";
	if($desc){echo '<meta property="og:description" content="'.esc_attr($desc).'">'."\n";}
	echo '<meta property="og:url" content="'.esc_url($url).'">'."\n";
	if($img){echo '<meta property="og:image" content="'.esc_url($img).'">'."\n";}
	echo '<meta name="twitter:card" content="summary_large_image">'."\n";
	echo '<meta name="twitter:title" content="'.esc_attr($title).'">'."\n";
	if($desc){echo '<meta name="twitter:description" content="'.esc_attr($desc).'">'."\n";}
	if($img){echo '<meta name="twitter:image" content="'.esc_url($img).'">'."\n";}
	$type=diniz_studio_editable_value('schema_type',$post_id,'WebPage'); $allowed=array('WebPage','Article','BlogPosting','Service','Product','FAQPage','AboutPage','ContactPage'); $type=in_array($type,$allowed,true)?$type:'WebPage';
	$schema=array('@context'=>'https://schema.org','@type'=>$type,'name'=>$title,'url'=>$url,'description'=>$desc,'inLanguage'=>get_bloginfo('language')); if($img){$schema['image']=$img;}
	echo '<script type="application/ld+json">'.wp_json_encode(array_filter($schema),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>'."\n";
}
add_action('wp_head','diniz_studio_native_seo_meta',5);
