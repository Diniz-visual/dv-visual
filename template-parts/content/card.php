<?php
/**
 * Card used by native archive loops.
 *
 * @package DinizStudio
 */

$post_id         = get_the_ID();
$categories      = get_the_category( $post_id );
$primary_category = $categories ? $categories[0] : null;
$content          = get_post_field( 'post_content', $post_id );
$word_count       = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
$reading_time     = max( 1, (int) ceil( $word_count / 210 ) );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'dv-archive-card dv-blog-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="dv-archive-card__image dv-blog-card__visual" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?></a>
	<?php else : ?>
		<a class="dv-archive-card__image dv-blog-card__visual dv-blog-card__placeholder" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<span aria-hidden="true">✦</span>
		</a>
	<?php endif; ?>

	<div class="dv-blog-card__body">
		<div class="dv-archive-card__meta dv-blog-card__meta">
			<?php if ( $primary_category ) : ?>
				<a href="<?php echo esc_url( get_category_link( $primary_category ) ); ?>"><?php echo esc_html( $primary_category->name ); ?></a>
			<?php else : ?>
				<span><?php esc_html_e( 'Artigo', 'dv-visual' ); ?></span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</div>

		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>

		<footer class="dv-blog-card__footer">
			<span><?php echo esc_html( sprintf( _n( '%d min de leitura', '%d min de leitura', $reading_time, 'dv-visual' ), $reading_time ) ); ?></span>
			<a class="dv-text-link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Ler o artigo: %s', 'dv-visual' ), get_the_title() ) ); ?>"><?php esc_html_e( 'Ler artigo', 'dv-visual' ); ?> <span aria-hidden="true">↗</span></a>
		</footer>
	</div>
</article>
