<?php
/**
 * Native WordPress comments template.
 *
 * @package DinizStudio
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="dv-comments">
	<header class="dv-comments__header">
		<div>
			<span class="dv-comments__eyebrow"><?php esc_html_e( 'Conversa', 'dv-visual' ); ?></span>
			<h2 class="dv-comments__title">
				<?php
				if ( have_comments() ) {
					echo esc_html(
						sprintf(
							/* translators: %s: number of comments. */
							_n( '%s comentário', '%s comentários', get_comments_number(), 'dv-visual' ),
							number_format_i18n( get_comments_number() )
						)
					);
				} else {
					esc_html_e( 'Participe da conversa', 'dv-visual' );
				}
				?>
			</h2>
		</div>
		<p><?php esc_html_e( 'Compartilhe sua perspectiva. Seu comentário ajuda a ampliar esta conversa.', 'dv-visual' ); ?></p>
	</header>

	<?php if ( have_comments() ) : ?>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 56,
				)
			);
			?>
		</ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_form'           => 'dv-comments__form',
			'class_submit'         => 'dv-comments__submit',
			'title_reply'          => __( 'Deixe um comentário', 'dv-visual' ),
			'title_reply_to'       => __( 'Responder a %s', 'dv-visual' ),
			'cancel_reply_link'    => __( 'Cancelar resposta', 'dv-visual' ),
			'label_submit'         => __( 'Publicar comentário', 'dv-visual' ),
			'comment_notes_before' => '<p class="dv-comments__note">' . esc_html__( 'Seu e-mail não será publicado. Os campos obrigatórios estão indicados.', 'dv-visual' ) . '</p>',
			'comment_notes_after'  => '',
			'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Comentário', 'dv-visual' ) . ' <span aria-hidden="true">*</span></label><textarea id="comment" name="comment" rows="7" maxlength="65525" required></textarea></p>',
		)
	);
	?>
</section>
