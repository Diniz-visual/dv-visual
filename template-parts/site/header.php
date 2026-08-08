<?php
/**
 * Editable WordPress logo, menu and header CTA.
 *
 * @package DinizStudio
 */

$sticky_value = diniz_studio_content_field( 'header_sticky', 'option' );
$sticky       = '' === $sticky_value ? true : (bool) $sticky_value;
$transparent  = (bool) diniz_studio_content_field( 'header_transparent', 'option' );
$cta_label    = diniz_studio_content_field( 'header_cta_label', 'option' ) ?: __( 'Iniciar projeto', 'dv-visual' );
$cta_link     = diniz_studio_content_field( 'header_cta_url', 'option' );
$cta_url      = is_array( $cta_link ) && ! empty( $cta_link['url'] ) ? $cta_link['url'] : diniz_studio_menu_page_url( 'proposta' );
$cta_target   = is_array( $cta_link ) && ! empty( $cta_link['target'] ) ? $cta_link['target'] : '_self';
$header_logo  = diniz_studio_content_field( 'brand_logo_light', 'option' ) ?: diniz_studio_content_field( 'brand_logo_dark', 'option' );
$logo_markup  = $header_logo ? diniz_studio_acf_image( $header_logo, 'full', 'dv-managed-logo', 'eager' ) : '';
$classes      = array( 'dv-header', 'dv-header--home-glass', 'dv-header--global', 'alignfull' );

if ( ! $sticky ) {
	$classes[] = 'dv-header--static';
}
if ( $transparent ) {
	$classes[] = 'dv-header--transparent';
}
?>
<header class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="wp-block-group alignwide">
		<div class="wp-block-group dv-site-brand">
			<?php if ( $logo_markup ) : ?>
				<div class="wp-block-site-logo">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
						<?php echo $logo_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</div>
			<?php elseif ( has_custom_logo() ) : ?>
				<div class="wp-block-site-logo"><?php the_custom_logo(); ?></div>
			<?php endif; ?>
			<p class="wp-block-site-title">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			</p>
		</div>

		<?php echo diniz_studio_menu_block( array( 'location' => 'primary' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="wp-block-buttons">
			<div class="wp-block-button">
				<a class="wp-block-button__link has-accent-background-color has-background wp-element-button" href="<?php echo esc_url( $cta_url ); ?>" target="<?php echo esc_attr( $cta_target ); ?>">
					<?php echo esc_html( $cta_label ); ?>
				</a>
			</div>
		</div>
	</div>
</header>
