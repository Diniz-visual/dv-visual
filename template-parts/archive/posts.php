<?php
/**
 * Reusable native WordPress loop for posts, taxonomy and search archives.
 *
 * @package DinizStudio
 */

$kicker      = ! empty( $args['kicker'] ) ? $args['kicker'] : __( 'Journal', 'dv-visual' );
$title       = ! empty( $args['title'] ) ? $args['title'] : __( 'Conteúdos', 'dv-visual' );
$description = ! empty( $args['description'] ) ? $args['description'] : '';
$posts_page  = (int) get_option( 'page_for_posts' );
$blog_url    = $posts_page ? get_permalink( $posts_page ) : home_url( '/blog/' );
$categories  = get_categories(
	array(
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
$active_category = is_category() ? (int) get_queried_object_id() : 0;
?>
<main id="dv-main-content" class="dv-blog-index">
	<section class="wp-block-group alignfull dv-archive-hero dv-blog-index__hero">
		<?php /* BLOG: container principal do topo. Mantém todo o conteúdo alinhado ao restante do site. */ ?>
		<div class="wp-block-group alignwide dv-blog-shell dv-blog-index__hero-inner">
			<?php echo diniz_studio_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p class="dv-kicker dv-kicker-dark"><?php echo esc_html( wp_strip_all_tags( $kicker ) ); ?></p>
			<h1 class="has-xxl-font-size"><?php echo wp_kses_post( $title ); ?></h1>
			<?php if ( $description ) : ?><div class="has-l-font-size"><?php echo wp_kses_post( $description ); ?></div><?php endif; ?>
		</div>
	</section>

	<section class="dv-blog-filter-section" aria-labelledby="dv-blog-filter-title">
		<div class="dv-blog-filter-shell">
			<div class="dv-blog-filter-heading">
				<div>
					<p class="dv-kicker" id="dv-blog-filter-title"><?php echo esc_html( diniz_studio_global_text( 'dv_blog_filter_kicker', 'Explore por assunto' ) ); ?></p>
					<h2><?php echo esc_html( diniz_studio_global_text( 'dv_blog_filter_title', 'Encontre o conteúdo certo para agora.' ) ); ?></h2>
				</div>
				<p>
					<?php
					echo $active_category
						? esc_html__( 'Exibindo uma categoria selecionada.', 'dv-visual' )
						: esc_html__( 'Exibindo todos os artigos publicados.', 'dv-visual' );
					?>
				</p>
			</div>
		</div>
	</section>

	<section class="wp-block-group dv-archive-grid dv-blog-index__articles" aria-label="<?php esc_attr_e( 'Artigos publicados', 'dv-visual' ); ?>">
		<div class="dv-blog-content-layout">
			<div class="dv-blog-content-column">
				<?php if ( $active_category ) : ?>
					<div class="dv-blog-filter-reset">
						<a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Limpar filtro', 'dv-visual' ); ?> <span aria-hidden="true">×</span></a>
					</div>
				<?php endif; ?>

				<div class="dv-posts-grid alignwide">
					<?php if ( have_posts() ) : ?>
						<?php while ( have_posts() ) : the_post(); ?>
							<?php get_template_part( 'template-parts/content/card' ); ?>
						<?php endwhile; ?>
					<?php else : ?>
						<?php get_template_part( 'template-parts/content/none' ); ?>
					<?php endif; ?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'           => 1,
						'end_size'           => 1,
						'prev_text'          => '<span aria-hidden="true">←</span> ' . __( 'Anterior', 'dv-visual' ),
						'next_text'          => __( 'Próxima', 'dv-visual' ) . ' <span aria-hidden="true">→</span>',
						'before_page_number' => '<span class="screen-reader-text">' . __( 'Página', 'dv-visual' ) . ' </span>',
						'screen_reader_text' => __( 'Navegação entre páginas do blog', 'dv-visual' ),
					)
				);
				?>
			</div>

			<aside class="dv-blog-category-aside" aria-labelledby="dv-blog-category-title">
				<div class="dv-blog-category-sticky">
					<p class="dv-kicker"><?php echo esc_html( diniz_studio_global_text( 'dv_blog_categories_kicker', 'Navegue por assunto' ) ); ?></p>
					<h2 id="dv-blog-category-title"><?php echo esc_html( diniz_studio_global_text( 'dv_blog_categories_title', 'Categorias' ) ); ?></h2>
					<label for="dv-blog-category-select"><?php esc_html_e( 'Escolha uma categoria', 'dv-visual' ); ?></label>
					<div class="dv-blog-category-select-wrap">
						<select id="dv-blog-category-select" data-dv-blog-category>
							<option value="<?php echo esc_url( $blog_url ); ?>"<?php selected( 0, $active_category ); ?>>
								<?php esc_html_e( 'Todos os artigos', 'dv-visual' ); ?>
							</option>
							<?php foreach ( $categories as $category ) : ?>
								<option value="<?php echo esc_url( get_category_link( $category ) ); ?>"<?php selected( $active_category, (int) $category->term_id ); ?>>
									<?php echo esc_html( $category->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<span aria-hidden="true">⌄</span>
					</div>
					<p class="dv-blog-category-help">
						<?php esc_html_e( 'O filtro acompanha sua leitura para você trocar de assunto a qualquer momento.', 'dv-visual' ); ?>
					</p>
					<noscript>
						<nav aria-label="<?php esc_attr_e( 'Links de categorias', 'dv-visual' ); ?>">
							<a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'Todos os artigos', 'dv-visual' ); ?></a>
							<?php foreach ( $categories as $category ) : ?>
								<a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
							<?php endforeach; ?>
						</nav>
					</noscript>
				</div>
			</aside>
		</div>
	</section>
</main>
