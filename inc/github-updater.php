<?php
/**
 * Native GitHub Release updater for the DV Visual theme.
 *
 * The official repository and package name are embedded in the theme. Every
 * public release must include an installable ZIP whose root is dv-visual.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the official GitHub repository URL embedded in the theme.
 *
 * A constant can still override it on managed installations:
 * define( 'DV_VISUAL_GITHUB_REPOSITORY', 'https://github.com/owner/repo' );
 *
 * @return string
 */
function diniz_studio_github_repository_url() {
	$url = 'https://github.com/Diniz-visual/dv-visual';

	if ( defined( 'DV_VISUAL_GITHUB_REPOSITORY' ) && DV_VISUAL_GITHUB_REPOSITORY ) {
		$url = (string) DV_VISUAL_GITHUB_REPOSITORY;
	}

	return esc_url_raw( (string) apply_filters( 'diniz_studio_github_repository_url', $url ) );
}

/**
 * Return the expected release asset name.
 *
 * @return string
 */
function diniz_studio_github_release_asset_name() {
	$value = apply_filters( 'diniz_studio_github_release_asset_name', 'dv-visual.zip' );
	$value = sanitize_file_name( (string) $value );
	return $value ?: 'dv-visual.zip';
}

/**
 * Extract owner and repository from a canonical GitHub URL.
 *
 * @param string $url Repository URL.
 * @return array{owner:string,repo:string}|WP_Error
 */
function diniz_studio_parse_github_repository( $url ) {
	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) || 'github.com' !== strtolower( $parts['host'] ) || empty( $parts['path'] ) ) {
		return new WP_Error( 'dv_github_invalid_url', __( 'Use uma URL completa do GitHub, como https://github.com/usuario/repositorio.', 'dv-visual' ) );
	}

	$segments = array_values( array_filter( explode( '/', trim( $parts['path'], '/' ) ) ) );
	if ( count( $segments ) < 2 ) {
		return new WP_Error( 'dv_github_invalid_path', __( 'A URL precisa conter o usuário e o nome do repositório.', 'dv-visual' ) );
	}
	$owner = sanitize_text_field( $segments[0] );
	$repo  = sanitize_text_field( preg_replace( '/\.git$/i', '', $segments[1] ) );
	if ( ! preg_match( '/^[0-9A-Za-z][0-9A-Za-z._-]*$/', $owner ) || ! preg_match( '/^[0-9A-Za-z][0-9A-Za-z._-]*$/', $repo ) ) {
		return new WP_Error( 'dv_github_invalid_repository', __( 'O usuário ou o nome do repositório contém caracteres inválidos.', 'dv-visual' ) );
	}

	return array(
		'owner' => $owner,
		'repo'  => $repo,
	);
}

/**
 * Optional token for private repositories or stricter GitHub API limits.
 *
 * Keep the token outside the database by defining DV_VISUAL_GITHUB_TOKEN in
 * wp-config.php. Public repositories do not require a token.
 *
 * @return string
 */
function diniz_studio_github_token() {
	$token = defined( 'DV_VISUAL_GITHUB_TOKEN' ) ? (string) DV_VISUAL_GITHUB_TOKEN : '';
	return (string) apply_filters( 'diniz_studio_github_token', $token );
}

/**
 * Return the local update package stored beside the installed theme folder.
 *
 * This fallback is useful in local and staging environments: replacing
 * wp-content/themes/dv-visual.zip with a newer package makes the native
 * WordPress update available without requiring GitHub credentials.
 *
 * @return string
 */
function diniz_studio_local_update_package_path() {
	$path = trailingslashit( get_theme_root() ) . 'dv-visual.zip';
	return (string) apply_filters( 'diniz_studio_local_update_package_path', $path );
}

/**
 * Read the DV Visual version directly from a local ZIP package.
 *
 * @return array<string,string>|false|WP_Error
 */
function diniz_studio_local_theme_release() {
	$package = diniz_studio_local_update_package_path();
	if ( ! $package || ! is_readable( $package ) ) {
		return false;
	}

	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'dv_local_zip_support_missing', __( 'O servidor não possui suporte para ler arquivos ZIP.', 'dv-visual' ) );
	}

	$zip    = new ZipArchive();
	$opened = $zip->open( $package );
	if ( true !== $opened ) {
		return new WP_Error( 'dv_local_zip_invalid', __( 'O pacote local dv-visual.zip não pôde ser aberto.', 'dv-visual' ) );
	}

	$stylesheet = $zip->getFromName( 'dv-visual/style.css' );
	$zip->close();
	if ( false === $stylesheet ) {
		return new WP_Error( 'dv_local_zip_structure', __( 'O pacote local precisa conter dv-visual/style.css.', 'dv-visual' ) );
	}

	if ( ! preg_match( '/^[ \t\/*#@]*Version:\s*([^\r\n]+)/mi', $stylesheet, $matches ) ) {
		return new WP_Error( 'dv_local_zip_version_missing', __( 'O pacote local não informa uma versão válida.', 'dv-visual' ) );
	}

	$version = sanitize_text_field( trim( $matches[1] ) );
	if ( ! preg_match( '/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
		return new WP_Error( 'dv_local_zip_version_invalid', __( 'A versão do pacote local é inválida.', 'dv-visual' ) );
	}

	return array(
		'version'   => $version,
		'url'       => admin_url( 'themes.php' ),
		'package'   => 'dv-visual-local://update',
		'published' => gmdate( 'c', (int) filemtime( $package ) ),
		'source'    => 'local',
	);
}

/**
 * Fetch and normalize the latest GitHub Release.
 *
 * @param bool $force Ignore the short theme-side cache.
 * @return array<string,string>|WP_Error
 */
function diniz_studio_github_latest_release( $force = false ) {
	$repository_url = diniz_studio_github_repository_url();
	if ( ! $repository_url ) {
		return new WP_Error( 'dv_github_not_configured', __( 'O repositório GitHub ainda não foi configurado.', 'dv-visual' ) );
	}

	$repository = diniz_studio_parse_github_repository( $repository_url );
	if ( is_wp_error( $repository ) ) {
		return $repository;
	}

	$cache_key = 'dv_visual_gh_' . md5( $repository['owner'] . '/' . $repository['repo'] );
	if ( $force ) {
		delete_site_transient( $cache_key );
	} else {
		$cached = get_site_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$headers = array(
		'Accept'               => 'application/vnd.github+json',
		'User-Agent'           => 'DV-Visual-WordPress/' . DINIZ_STUDIO_VERSION,
		'X-GitHub-Api-Version' => '2022-11-28',
	);
	$token   = diniz_studio_github_token();
	if ( $token ) {
		$headers['Authorization'] = 'Bearer ' . $token;
	}

	$api_url  = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', rawurlencode( $repository['owner'] ), rawurlencode( $repository['repo'] ) );
	$response = wp_safe_remote_get(
		$api_url,
		array(
			'timeout'     => 12,
			'redirection' => 3,
			'headers'     => $headers,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( 404 === $status ) {
		return new WP_Error( 'dv_github_release_unavailable', __( 'O GitHub retornou 404: o repositório pode estar privado ou indisponível, ou não ter uma Release estável publicada. Repositórios privados exigem autenticação no servidor WordPress.', 'dv-visual' ) );
	}
	if ( 200 !== $status ) {
		return new WP_Error(
			'dv_github_http_error',
			sprintf( __( 'O GitHub respondeu com o código %d. Confirme a URL do repositório e se existe uma Release publicada.', 'dv-visual' ), $status )
		);
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
		return new WP_Error( 'dv_github_invalid_release', __( 'A resposta do GitHub não contém uma versão válida.', 'dv-visual' ) );
	}

	$asset_name = diniz_studio_github_release_asset_name();
	$package    = '';
	$asset_api  = '';
	foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
		if ( $asset_name !== (string) ( $asset['name'] ?? '' ) ) {
			continue;
		}
		$package   = esc_url_raw( (string) ( $asset['browser_download_url'] ?? '' ) );
		$asset_api = esc_url_raw( (string) ( $asset['url'] ?? '' ) );
		break;
	}

	if ( ! $package ) {
		return new WP_Error(
			'dv_github_asset_missing',
			sprintf( __( 'A Release mais recente não contém o arquivo %s.', 'dv-visual' ), $asset_name )
		);
	}

	$version = ltrim( sanitize_text_field( (string) $data['tag_name'] ), "vV \t\n\r\0\x0B" );
	if ( ! preg_match( '/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
		return new WP_Error( 'dv_github_invalid_version', __( 'Use uma tag de versão como 4.28.0 ou v4.28.0.', 'dv-visual' ) );
	}

	$release = array(
		'version'   => $version,
		'url'       => esc_url_raw( (string) ( $data['html_url'] ?? $repository_url ) ),
		'package'   => $token && $asset_api ? $asset_api : $package,
		'published' => sanitize_text_field( (string) ( $data['published_at'] ?? '' ) ),
	);

	set_site_transient( $cache_key, $release, 30 * MINUTE_IN_SECONDS );
	delete_site_transient( 'dv_visual_github_update_error' );
	return $release;
}

/**
 * Add the GitHub release to WordPress' native Themes update screen.
 *
 * @param object $transient Theme update transient.
 * @return object
 */
function diniz_studio_github_theme_update( $transient ) {
	if ( ! is_object( $transient ) || empty( $transient->checked ) || ! diniz_studio_github_repository_url() ) {
		return $transient;
	}

	$stylesheet = get_template();
	$current    = isset( $transient->checked[ $stylesheet ] ) ? (string) $transient->checked[ $stylesheet ] : DINIZ_STUDIO_VERSION;
	$local      = diniz_studio_local_theme_release();
	$remote     = diniz_studio_github_latest_release();
	$release    = is_array( $local ) ? $local : false;

	/* A local fallback must not hide a newer GitHub release. */
	if ( is_array( $remote ) && ( ! $release || version_compare( $remote['version'], $release['version'], '>' ) ) ) {
		$release = $remote;
	}
	if ( is_wp_error( $remote ) ) {
		set_site_transient( 'dv_visual_github_update_error', $remote->get_error_message(), 30 * MINUTE_IN_SECONDS );
	} else {
		delete_site_transient( 'dv_visual_github_update_error' );
	}

	$transient->response  = isset( $transient->response ) && is_array( $transient->response ) ? $transient->response : array();
	$transient->no_update = isset( $transient->no_update ) && is_array( $transient->no_update ) ? $transient->no_update : array();
	unset( $transient->response[ $stylesheet ], $transient->no_update[ $stylesheet ] );

	if ( ! $release ) {
		return $transient;
	}

	$update = array(
		'theme'        => $stylesheet,
		'new_version'  => $release['version'],
		'url'          => $release['url'],
		'package'      => $release['package'],
		'requires'     => '6.5',
		'requires_php' => '8.0',
	);

	if ( version_compare( $release['version'], $current, '>' ) ) {
		$transient->response[ $stylesheet ] = $update;
	} else {
		$transient->no_update[ $stylesheet ] = $update;
	}

	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'diniz_studio_github_theme_update', 20 );

/**
 * Copy the local package to a disposable file for the native upgrader.
 *
 * Returning the original path would allow WordPress to delete the package
 * after installation, so the upgrader always receives a temporary copy.
 *
 * @param false|string|WP_Error $reply      Existing pre-download result.
 * @param string                $package    Package URL or local sentinel.
 * @param WP_Upgrader           $upgrader   Current upgrader instance.
 * @param array<string,mixed>   $hook_extra Upgrade context.
 * @return false|string|WP_Error
 */
function diniz_studio_local_update_pre_download( $reply, $package, $upgrader, $hook_extra ) {
	unset( $upgrader, $hook_extra );
	if ( 'dv-visual-local://update' !== $package ) {
		return $reply;
	}

	$source = diniz_studio_local_update_package_path();
	if ( ! is_readable( $source ) ) {
		return new WP_Error( 'dv_local_zip_missing', __( 'O pacote local dv-visual.zip não está disponível.', 'dv-visual' ) );
	}

	$temporary = wp_tempnam( 'dv-visual.zip' );
	if ( ! $temporary || ! copy( $source, $temporary ) ) {
		if ( $temporary && file_exists( $temporary ) ) {
			wp_delete_file( $temporary );
		}
		return new WP_Error( 'dv_local_zip_copy_failed', __( 'O WordPress não conseguiu preparar o pacote local para atualização.', 'dv-visual' ) );
	}

	return $temporary;
}
add_filter( 'upgrader_pre_download', 'diniz_studio_local_update_pre_download', 10, 4 );

/**
 * Authenticate GitHub's asset API when a private repository token is set.
 *
 * @param array<string,mixed> $args Request arguments.
 * @param string              $url  Request URL.
 * @return array<string,mixed>
 */
function diniz_studio_github_http_headers( $args, $url ) {
	$token = diniz_studio_github_token();
	$host  = wp_parse_url( $url, PHP_URL_HOST );
	$path  = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! $token || 'api.github.com' !== strtolower( (string) $host ) || false === strpos( (string) $path, '/releases/assets/' ) ) {
		return $args;
	}

	$args['headers']                  = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
	$args['headers']['Authorization'] = 'Bearer ' . $token;
	$args['headers']['Accept']        = 'application/octet-stream';
	$args['headers']['User-Agent']    = 'DV-Visual-WordPress/' . DINIZ_STUDIO_VERSION;
	return $args;
}
add_filter( 'http_request_args', 'diniz_studio_github_http_headers', 20, 2 );

/**
 * Clear update caches whenever the theme changes.
 *
 * @return void
 */
function diniz_studio_clear_github_update_cache() {
	$repository = diniz_studio_parse_github_repository( diniz_studio_github_repository_url() );
	if ( ! is_wp_error( $repository ) ) {
		delete_site_transient( 'dv_visual_gh_' . md5( $repository['owner'] . '/' . $repository['repo'] ) );
	}
	delete_site_transient( 'dv_visual_github_update_error' );
	delete_site_transient( 'update_themes' );
}

/**
 * Honor WordPress' "Check again" before its own update callback runs.
 *
 * @return void
 */
function diniz_studio_force_github_update_check() {
	if ( current_user_can( 'update_themes' ) && ! empty( $_GET['force-check'] ) ) {
		diniz_studio_clear_github_update_cache();
	}
}
add_action( 'load-update-core.php', 'diniz_studio_force_github_update_check', 1 );

/**
 * Clear the cached release after an update finishes.
 *
 * @param WP_Upgrader $upgrader Upgrader instance.
 * @param array       $options  Completed operation data.
 * @return void
 */
function diniz_studio_clear_github_cache_after_upgrade( $upgrader, $options ) {
	if ( 'theme' === ( $options['type'] ?? '' ) ) {
		diniz_studio_clear_github_update_cache();
	}
}
add_action( 'upgrader_process_complete', 'diniz_studio_clear_github_cache_after_upgrade', 10, 2 );

/**
 * Remove errors saved by the configurable updater when this embedded updater
 * first becomes active.
 *
 * @return void
 */
function diniz_studio_clear_legacy_github_update_error() {
	if ( DINIZ_STUDIO_VERSION === get_option( 'dv_visual_updater_version', '' ) ) {
		return;
	}

	diniz_studio_clear_github_update_cache();
	update_option( 'dv_visual_updater_version', DINIZ_STUDIO_VERSION, false );
}
add_action( 'admin_init', 'diniz_studio_clear_legacy_github_update_error', 1 );

/**
 * Surface GitHub connection errors only on update-related admin screens.
 *
 * @return void
 */
function diniz_studio_github_update_notice() {
	if ( ! current_user_can( 'update_themes' ) || ! diniz_studio_github_repository_url() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'themes', 'update-core', 'themes-network', 'update-core-network' ), true ) ) {
		return;
	}

	$error = get_site_transient( 'dv_visual_github_update_error' );
	if ( $error ) {
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Atualização DV Visual:', 'dv-visual' ),
			esc_html( $error )
		);
	}
}
add_action( 'admin_notices', 'diniz_studio_github_update_notice' );
add_action( 'network_admin_notices', 'diniz_studio_github_update_notice' );
