<?php
/**
 * Title: Perguntas frequentes
 * Slug: diniz-studio/faq
 * Categories: dv-content
 */

$faq_items = get_posts(
	array(
		'post_type'      => 'faq',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'ASC',
		),
		'order'          => 'ASC',
	)
);
?>
<section class="wp-block-group alignfull has-paper-background-color has-background dv-faq-section">
	<div class="wp-block-columns alignwide">
		<div class="wp-block-column">
			<p class="dv-kicker"><?php echo esc_html( diniz_studio_global_text( 'dv_faq_kicker', 'Dúvidas frequentes' ) ); ?></p>
			<h2><?php echo esc_html( diniz_studio_global_text( 'dv_faq_title', 'Antes de começar.' ) ); ?></h2>
			<p><?php echo esc_html( diniz_studio_global_text( 'dv_faq_text', 'Respostas objetivas para as perguntas mais comuns.' ) ); ?></p>
		</div>
		<div class="wp-block-column dv-faq-list">
			<?php if ( $faq_items ) : ?>
				<?php foreach ( $faq_items as $faq_item ) : ?>
					<details class="wp-block-details dv-faq-item">
						<summary><?php echo esc_html( get_the_title( $faq_item ) ); ?></summary>
						<div class="dv-faq-answer"><?php echo apply_filters( 'the_content', $faq_item->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					</details>
				<?php endforeach; ?>
			<?php elseif ( current_user_can( 'edit_posts' ) ) : ?>
				<p>Nenhuma pergunta frequente publicada ainda.</p>
			<?php endif; ?>
		</div>
	</div>
</section>
