<?php
/** Title: Chamada para ação DV | Slug: diniz-studio/cta | Categories: dv-conversion */
$kicker=diniz_studio_global_text('dv_cta_kicker','Seu próximo capítulo começa aqui');
$title=diniz_studio_global_text('dv_cta_title','Vamos criar uma marca que ninguém confunde?');
$text=diniz_studio_global_text('dv_cta_text','Conte sobre o seu momento. A primeira conversa é simples, estratégica e sem compromisso.');
$link=diniz_studio_editable_value('dv_cta_link','option',array());
if(!is_array($link)||empty($link['url'])){$link=array('url'=>home_url('/proposta/'),'title'=>'Iniciar um projeto','target'=>'_self');}
?>
<div class="wp-block-group alignfull dv-cta-wrap"><div class="wp-block-group alignwide dv-cta dv-glass"><span class="dv-cta-orb"></span><p class="dv-kicker dv-kicker-dark"><?php echo esc_html($kicker); ?></p><h2 class="wp-block-heading has-xl-font-size"><?php echo nl2br(esc_html($title)); ?></h2><p class="has-l-font-size"><?php echo esc_html($text); ?></p><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link has-accent-background-color has-background wp-element-button" href="<?php echo esc_url($link['url']); ?>" target="<?php echo esc_attr($link['target']?:'_self'); ?>"><?php echo esc_html($link['title']?:'Iniciar um projeto'); ?></a></div></div></div></div>
