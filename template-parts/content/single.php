<?php
/**
 * Complete editorial template for individual blog posts.
 *
 * PANEL: title, excerpt, featured image, categories, author and content are
 * managed in Posts. H2 and H3 headings automatically become the side summary.
 *
 * @package DinizStudio
 */

$posts_page    = (int) get_option( 'page_for_posts' );
$blog_url      = $posts_page ? get_permalink( $posts_page ) : home_url( '/blog/' );
$all_categories = get_categories(
	array(
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
?>
<main id="dv-main-content" class="dv-blog-single">
	<?php
	while ( have_posts() ) :
		the_post();
		$post_id            = get_the_ID();
		$content            = get_post_field( 'post_content', $post_id );
		$word_count         = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
		$reading_time       = max( 1, (int) ceil( $word_count / 210 ) );
		$post_categories    = get_the_category( $post_id );
		$current_categories = wp_list_pluck( $post_categories, 'term_id' );
		$author_id          = (int) get_the_author_meta( 'ID' );
		$author_bio         = get_the_author_meta( 'description', $author_id );
		$previous_post      = get_previous_post();
		$next_post          = get_next_post();
		$share_url          = get_permalink( $post_id );
		$share_title        = get_the_title( $post_id );
		$quick_answer       = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $content ) ), 34 );
		?>

		<section class="dv-page-hero dv-blog-single__hero">
			<div class="dv-blog-shell">
				<?php echo diniz_studio_breadcrumbs( array(), 'on-light' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<div class="dv-blog-single__heading">
					<div class="dv-single-taxonomy">
						<?php if ( $post_categories ) : ?>
							<?php foreach ( $post_categories as $category ) : ?>
								<a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
							<?php endforeach; ?>
						<?php else : ?>
							<span><?php esc_html_e( 'Artigo', 'dv-visual' ); ?></span>
						<?php endif; ?>
					</div>

					<h1><?php the_title(); ?></h1>

					<?php if ( $quick_answer ) : ?>
						<p class="dv-blog-single__lead"><?php echo esc_html( $quick_answer ); ?></p>
					<?php endif; ?>

					<div class="dv-blog-single__meta">
						<span class="dv-blog-single__author">
							<?php echo get_avatar( $author_id, 44, '', '', array( 'class' => 'dv-blog-single__avatar' ) ); ?>
							<span><small><?php esc_html_e( 'Por', 'dv-visual' ); ?></small><strong><?php the_author(); ?></strong></span>
						</span>
						<span><small><?php esc_html_e( 'Publicado em', 'dv-visual' ); ?></small><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
						<span><small><?php esc_html_e( 'Leitura', 'dv-visual' ); ?></small><strong><?php echo esc_html( sprintf( _n( '%d minuto', '%d minutos', $reading_time, 'dv-visual' ), $reading_time ) ); ?></strong></span>
					</div>
				</div>
			</div>
		</section>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'dv-blog-shell dv-blog-single__body' ); ?>>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="dv-blog-single__featured">
					<?php the_post_thumbnail( 'full', array( 'class' => 'dv-blog-single__image', 'loading' => 'eager' ) ); ?>
				</figure>
			<?php endif; ?>

			<aside class="dv-blog-share" aria-label="<?php esc_attr_e( 'Compartilhar artigo', 'dv-visual' ); ?>">
				<strong><?php esc_html_e( 'Compartilhar', 'dv-visual' ); ?></strong>
				<div>
					<a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $share_url ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Compartilhar no LinkedIn', 'dv-visual' ); ?>"><span class="dashicons dashicons-linkedin" aria-hidden="true"></span></a>
					<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $share_url ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Compartilhar no Facebook', 'dv-visual' ); ?>"><span class="dashicons dashicons-facebook-alt" aria-hidden="true"></span></a>
					<a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $share_title . ' ' . $share_url ) ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Compartilhar no WhatsApp', 'dv-visual' ); ?>"><span class="dashicons dashicons-whatsapp" aria-hidden="true"></span></a>
					<button type="button" data-dv-copy-url="<?php echo esc_url( $share_url ); ?>" data-label="<?php esc_attr_e( 'Copiar link', 'dv-visual' ); ?>" data-success="<?php esc_attr_e( 'Link copiado', 'dv-visual' ); ?>" aria-label="<?php esc_attr_e( 'Copiar link do artigo', 'dv-visual' ); ?>"><span class="dashicons dashicons-admin-links" aria-hidden="true"></span></button>
				</div>
				<span class="dv-blog-share__status" data-dv-copy-status aria-live="polite"></span>
			</aside>

			<div class="dv-blog-single__layout dv-cpt-single__layout">
				<div class="dv-entry-content dv-blog-content" data-dv-toc="true">
					<?php if ( $quick_answer ) : ?>
						<aside class="dv-blog-quick-answer" aria-label="<?php esc_attr_e( 'Resumo do artigo', 'dv-visual' ); ?>">
							<strong><?php esc_html_e( 'Resposta rápida', 'dv-visual' ); ?></strong>
							<p><?php echo esc_html( $quick_answer ); ?></p>
						</aside>
					<?php endif; ?>
					<?php echo apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php wp_link_pages(); ?>

					<?php if ( has_tag() ) : ?>
						<footer class="dv-blog-tags">
							<strong><?php esc_html_e( 'Assuntos', 'dv-visual' ); ?></strong>
							<div><?php the_tags( '', '' ); ?></div>
						</footer>
					<?php endif; ?>
				</div>

				<aside class="dv-cpt-single__aside dv-blog-aside" aria-label="<?php esc_attr_e( 'Navegação do artigo', 'dv-visual' ); ?>">
					<nav class="dv-article-toc dv-glass-card" aria-label="<?php esc_attr_e( 'Sumário do artigo', 'dv-visual' ); ?>">
						<strong><?php esc_html_e( 'Neste artigo', 'dv-visual' ); ?> <span data-dv-toc-count></span></strong>
						<ol></ol>
					</nav>

					<?php if ( $all_categories ) : ?>
						<nav class="dv-blog-categories dv-glass-card" aria-label="<?php esc_attr_e( 'Categorias do blog', 'dv-visual' ); ?>">
							<strong><?php esc_html_e( 'Categorias', 'dv-visual' ); ?></strong>
							<ul>
								<?php foreach ( $all_categories as $category ) : ?>
									<li<?php echo in_array( $category->term_id, $current_categories, true ) ? ' class="is-current"' : ''; ?>>
									<a href="<?php echo esc_url( get_category_link( $category ) ); ?>">
										<span><?php echo esc_html( $category->name ); ?></span>
									</a>
									</li>
								<?php endforeach; ?>
							</ul>
							<a class="dv-blog-categories__all" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Ver todos os artigos', 'dv-visual' ); ?> <span aria-hidden="true">↗</span></a>
						</nav>
					<?php endif; ?>
				</aside>
			</div>

			<section class="dv-blog-author dv-glass-card" aria-label="<?php esc_attr_e( 'Sobre o autor', 'dv-visual' ); ?>">
				<?php echo get_avatar( $author_id, 96, '', '', array( 'class' => 'dv-blog-author__avatar' ) ); ?>
				<div>
					<small><?php esc_html_e( 'Escrito por', 'dv-visual' ); ?></small>
					<h2><?php the_author(); ?></h2>
					<p><?php echo esc_html( $author_bio ?: __( 'Conteúdo criado por nossa equipe para transformar estratégia, design e tecnologia em decisões mais claras.', 'dv-visual' ) ); ?></p>
					<a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php esc_html_e( 'Ver outros artigos', 'dv-visual' ); ?> <span aria-hidden="true">↗</span></a>
				</div>
			</section>

			<?php if ( $previous_post || $next_post ) : ?>
				<nav class="dv-blog-post-nav" aria-label="<?php esc_attr_e( 'Outros artigos', 'dv-visual' ); ?>">
					<?php if ( $previous_post ) : ?>
						<a href="<?php echo esc_url( get_permalink( $previous_post ) ); ?>">
							<small>← <?php esc_html_e( 'Artigo anterior', 'dv-visual' ); ?></small>
							<strong><?php echo esc_html( get_the_title( $previous_post ) ); ?></strong>
						</a>
					<?php endif; ?>
					<?php if ( $next_post ) : ?>
						<a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>">
							<small><?php esc_html_e( 'Próximo artigo', 'dv-visual' ); ?> →</small>
							<strong><?php echo esc_html( get_the_title( $next_post ) ); ?></strong>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

			<?php if ( comments_open() || get_comments_number() ) : ?>
				<?php comments_template(); ?>
			<?php endif; ?>
		</article>
	<?php endwhile; ?>

	<?php diniz_studio_render_pattern( 'cta' ); ?>
</main>
