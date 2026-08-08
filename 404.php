<?php
/**
 * Animated not-found page.
 *
 * @package DinizStudio
 */

get_header();

$posts_page   = (int) get_option( 'page_for_posts' );
$blog_url     = $posts_page ? get_permalink( $posts_page ) : home_url( '/blog/' );
$portfolio_url = get_post_type_archive_link( 'portfolio' ) ?: home_url( '/portfolio/' );
$services_url  = get_post_type_archive_link( 'service' ) ?: home_url( '/solucao/' );
$useful_links  = array(
	array(
		'label' => __( 'Ver portfólio', 'dv-visual' ),
		'text'  => __( 'Projetos, cases e resultados.', 'dv-visual' ),
		'url'   => $portfolio_url,
		'code'  => '01',
	),
	array(
		'label' => __( 'Conhecer soluções', 'dv-visual' ),
		'text'  => __( 'Estratégia, design e presença digital.', 'dv-visual' ),
		'url'   => $services_url,
		'code'  => '02',
	),
	array(
		'label' => __( 'Explorar o blog', 'dv-visual' ),
		'text'  => __( 'Ideias para marcas em movimento.', 'dv-visual' ),
		'url'   => $blog_url,
		'code'  => '03',
	),
);
?>
<main id="dv-main-content" class="dv-error-page">
	<section class="dv-error-hero">
		<div class="dv-error-hero__grid" aria-hidden="true"></div>
		<div class="dv-error-hero__glow dv-error-hero__glow--one" aria-hidden="true"></div>
		<div class="dv-error-hero__glow dv-error-hero__glow--two" aria-hidden="true"></div>

		<div class="dv-error-shell">
			<div class="dv-error-copy">
				<?php echo diniz_studio_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="dv-kicker dv-kicker-dark"><?php echo esc_html( diniz_studio_global_text( 'dv_404_kicker', 'Rota não encontrada' ) ); ?></p>
				<p class="dv-error-code"><?php esc_html_e( 'Erro // 404', 'dv-visual' ); ?></p>
				<h1><?php echo esc_html( diniz_studio_global_text( 'dv_404_title', 'Essa página se perdeu pelo caminho.' ) ); ?></h1>
				<p class="dv-error-lead"><?php esc_html_e( 'O endereço pode ter mudado ou nunca ter existido. Vamos encontrar uma nova direção para você.', 'dv-visual' ); ?></p>

				<div class="dv-error-search">
					<p><?php esc_html_e( 'Busque em todo o site', 'dv-visual' ); ?></p>
					<?php get_search_form(); ?>
				</div>

				<div class="dv-error-actions">
					<a class="wp-element-button dv-error-action--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php esc_html_e( 'Voltar ao início', 'dv-visual' ); ?>
						<span aria-hidden="true">↗</span>
					</a>
					<a class="dv-error-action--secondary" href="<?php echo esc_url( $portfolio_url ); ?>">
						<?php esc_html_e( 'Ver projetos', 'dv-visual' ); ?>
						<span aria-hidden="true">→</span>
					</a>
				</div>
			</div>

			<div class="dv-error-scene" data-dv-404-scene aria-hidden="true">
				<div class="dv-error-scene__status">
					<span></span>
					<?php esc_html_e( 'Procurando nova rota', 'dv-visual' ); ?>
				</div>
				<div class="dv-error-radar">
					<div class="dv-error-radar__orbit dv-error-radar__orbit--outer"></div>
					<div class="dv-error-radar__orbit dv-error-radar__orbit--middle"></div>
					<div class="dv-error-radar__orbit dv-error-radar__orbit--inner"></div>
					<div class="dv-error-radar__sweep"></div>
					<span class="dv-error-radar__dot dv-error-radar__dot--one"></span>
					<span class="dv-error-radar__dot dv-error-radar__dot--two"></span>
					<span class="dv-error-radar__dot dv-error-radar__dot--three"></span>
					<div class="dv-error-number">
						<span>4</span><span>0</span><span>4</span>
					</div>
				</div>
				<div class="dv-error-note dv-error-note--top">
					<small><?php esc_html_e( 'Coordenadas', 'dv-visual' ); ?></small>
					<strong><?php echo esc_html( diniz_studio_global_text( 'dv_404_kicker', 'Rota não encontrada' ) ); ?></strong>
				</div>
				<div class="dv-error-note dv-error-note--bottom">
					<small><?php esc_html_e( 'Status', 'dv-visual' ); ?></small>
					<strong><?php esc_html_e( 'Página fora do mapa', 'dv-visual' ); ?></strong>
				</div>
			</div>
		</div>

		<nav class="dv-error-links" aria-label="<?php esc_attr_e( 'Caminhos úteis', 'dv-visual' ); ?>">
			<?php foreach ( $useful_links as $useful_link ) : ?>
				<a href="<?php echo esc_url( $useful_link['url'] ); ?>">
					<span class="dv-error-links__code"><?php echo esc_html( $useful_link['code'] ); ?></span>
					<span>
						<strong><?php echo esc_html( $useful_link['label'] ); ?></strong>
						<small><?php echo esc_html( $useful_link['text'] ); ?></small>
					</span>
					<i aria-hidden="true">↗</i>
				</a>
			<?php endforeach; ?>
		</nav>
	</section>
</main>
<?php
get_footer();
