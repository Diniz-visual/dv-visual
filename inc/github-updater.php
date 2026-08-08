<?php
/**
 * GitHub theme updater for DV Visual.
 *
 * Allows this theme to receive updates through the native WordPress updater
 * using GitHub Releases as the source of truth.
 *
 * @package DinizStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DV_Visual_GitHub_Updater {
	const OPTION_KEY   = 'dv_visual_github_updater';
	const CACHE_KEY    = 'dv_visual_github_release';
	const SETTINGS_SLUG = 'dv-visual-github-updates';
	const GITHUB_OWNER = 'Diniz-visual';
	const GITHUB_REPOSITORY = 'dv-visual';

	/** @var DV_Visual_GitHub_Updater|null */
	private static $instance = null;

	/** @var array<string,mixed> */
	private $settings = array();

	/** @var string */
	private $theme_slug = '';

	/** @var string */
	private $theme_version = '';

	/** @var string */
	private $package_host = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$theme               = wp_get_theme( get_template() );
		$this->theme_slug     = $theme->get_stylesheet();
		$this->theme_version  = (string) $theme->get( 'Version' );
		$this->settings       = $this->get_settings();

		add_filter( 'pre_set_site_transient_update_themes', array( $this, 'inject_update' ) );
		add_filter( 'site_transient_update_themes', array( $this, 'inject_update' ) );
		add_filter( 'http_request_args', array( $this, 'authorize_github_request' ), 10, 2 );
		add_filter( 'upgrader_source_selection', array( $this, 'normalize_source_directory' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache_after_update' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_post_dv_visual_github_check', array( $this, 'manual_check' ) );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_settings() {
		$defaults = array(
			'owner'        => self::GITHUB_OWNER,
			'repository'   => self::GITHUB_REPOSITORY,
			'token'        => '',
			'prereleases'  => 0,
		);

		$stored = get_option( self::OPTION_KEY, array() );
		$settings = wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );

		// Repository is intentionally pinned in the theme so updates can only come
		// from the official DV Visual repository. Never source-control a PAT.
		$settings['owner'] = self::GITHUB_OWNER;
		$settings['repository'] = self::GITHUB_REPOSITORY;

		// Authentication is managed by the theme settings screen and stored
		// in WordPress options. The repository itself is pinned above.

		return $settings;
	}

	private function is_configured() {
		return ! empty( $this->settings['owner'] ) && ! empty( $this->settings['repository'] );
	}

	private function api_base() {
		return sprintf(
			'https://api.github.com/repos/%s/%s',
			rawurlencode( (string) $this->settings['owner'] ),
			rawurlencode( (string) $this->settings['repository'] )
		);
	}

	/**
	 * Fetch latest published release (or first published prerelease if enabled).
	 *
	 * @param bool $force Force bypass cache.
	 * @return array<string,mixed>|WP_Error|null
	 */
	private function get_release( $force = false ) {
		if ( ! $this->is_configured() ) {
			return null;
		}

		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$endpoint = ! empty( $this->settings['prereleases'] )
			? $this->api_base() . '/releases?per_page=10'
			: $this->api_base() . '/releases/latest';

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 12,
				'headers' => $this->github_headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$github_message = is_array( $body ) && ! empty( $body['message'] ) ? sanitize_text_field( $body['message'] ) : '';

			if ( 404 === $status ) {
				$repo_check = wp_remote_get(
					$this->api_base(),
					array(
						'timeout' => 12,
						'headers' => $this->github_headers(),
					)
				);

				if ( ! is_wp_error( $repo_check ) && 200 === (int) wp_remote_retrieve_response_code( $repo_check ) ) {
					return new WP_Error(
						'dv_github_no_release',
						__( 'O repositório foi encontrado e o token tem acesso, mas ainda não existe uma GitHub Release publicada. Crie uma Release (não apenas uma tag) e tente novamente.', 'dv-visual' )
					);
				}

				return new WP_Error(
					'dv_github_repo_not_found',
					__( 'O GitHub não encontrou o repositório ou o token não possui acesso a ele. Confira Usuário/Organização, Repositório e as permissões do fine-grained token.', 'dv-visual' )
				);
			}

			return new WP_Error(
				'dv_github_http_error',
				sprintf(
					__( 'GitHub retornou HTTP %1$d ao verificar atualizações.%2$s', 'dv-visual' ),
					$status,
					$github_message ? ' ' . $github_message : ''
				)
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'dv_github_invalid_json', __( 'Resposta inválida recebida do GitHub.', 'dv-visual' ) );
		}

		if ( ! empty( $this->settings['prereleases'] ) ) {
			$release = null;
			foreach ( $data as $candidate ) {
				if ( is_array( $candidate ) && empty( $candidate['draft'] ) ) {
					$release = $candidate;
					break;
				}
			}
			$data = $release;
		}

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return new WP_Error( 'dv_github_no_release', __( 'Nenhuma release publicada foi encontrada no GitHub.', 'dv-visual' ) );
		}

		$normalized = array(
			'version'      => ltrim( (string) $data['tag_name'], "vV \t\n\r\0\x0B" ),
			'tag'          => (string) $data['tag_name'],
			'name'         => isset( $data['name'] ) ? (string) $data['name'] : (string) $data['tag_name'],
			'url'          => isset( $data['html_url'] ) ? esc_url_raw( $data['html_url'] ) : '',
			'package'      => isset( $data['zipball_url'] ) ? esc_url_raw( $data['zipball_url'] ) : '',
			'body'         => isset( $data['body'] ) ? wp_kses_post( $data['body'] ) : '',
			'published_at' => isset( $data['published_at'] ) ? sanitize_text_field( $data['published_at'] ) : '',
			'assets'       => isset( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : array(),
		);

		// Prefer a release asset explicitly built for WordPress when available.
		// This avoids installing repository-only files or a nested project root.
		foreach ( $normalized['assets'] as $asset ) {
			if ( ! is_array( $asset ) || empty( $asset['name'] ) || empty( $asset['url'] ) ) {
				continue;
			}
			$name = strtolower( (string) $asset['name'] );
			if ( in_array( $name, array( 'dv-visual.zip', 'dv-visual-theme.zip', 'theme-dv-visual.zip' ), true ) ) {
				$normalized['package'] = esc_url_raw( $asset['url'] );
				break;
			}
		}

		set_site_transient( self::CACHE_KEY, $normalized, 15 * MINUTE_IN_SECONDS );
		return $normalized;
	}

	/**
	 * Add GitHub update data to the native WordPress theme update transient.
	 *
	 * @param mixed $transient Update transient.
	 * @return mixed
	 */
	public function inject_update( $transient ) {
		if ( ! $this->is_configured() || ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_release();
		if ( is_wp_error( $release ) || ! is_array( $release ) || empty( $release['version'] ) || empty( $release['package'] ) ) {
			return $transient;
		}

		$update = array(
			'theme'       => $this->theme_slug,
			'new_version' => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['package'],
			'requires'    => '6.2',
			'requires_php'=> '7.4',
		);

		if ( version_compare( $release['version'], $this->theme_version, '>' ) ) {
			if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
				$transient->response = array();
			}
			$transient->response[ $this->theme_slug ] = $update;
		} else {
			if ( isset( $transient->response[ $this->theme_slug ] ) ) {
				unset( $transient->response[ $this->theme_slug ] );
			}
			if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
				$transient->no_update = array();
			}
			$transient->no_update[ $this->theme_slug ] = $update;
		}

		return $transient;
	}

	/**
	 * GitHub API headers, including optional private-repository token.
	 *
	 * @return array<string,string>
	 */
	private function github_headers() {
		$headers = array(
			'Accept'               => 'application/vnd.github+json',
			'X-GitHub-Api-Version' => '2022-11-28',
			'User-Agent'           => 'DV-Visual-WordPress-Updater/' . $this->theme_version,
		);

		if ( ! empty( $this->settings['token'] ) ) {
			$headers['Authorization'] = 'Bearer ' . trim( (string) $this->settings['token'] );
		}
		return $headers;
	}

	/**
	 * Add authentication when WordPress downloads from api.github.com.
	 *
	 * @param array<string,mixed> $args HTTP args.
	 * @param string              $url Request URL.
	 * @return array<string,mixed>
	 */
	public function authorize_github_request( $args, $url ) {
		if ( empty( $this->settings['token'] ) || 0 !== strpos( $url, 'https://api.github.com/' ) ) {
			return $args;
		}

		$repo_prefix = $this->api_base();
		if ( 0 !== strpos( $url, $repo_prefix ) ) {
			return $args;
		}

		if ( ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
			$args['headers'] = array();
		}

		$headers = $this->github_headers();
		// GitHub release assets require octet-stream to return the binary file.
		if ( false !== strpos( $url, '/releases/assets/' ) ) {
			$headers['Accept'] = 'application/octet-stream';
		}

		$args['headers'] = array_merge( $args['headers'], $headers );
		return $args;
	}

	/**
	 * GitHub archives unpack to OWNER-REPO-HASH. Rename them to the stable theme
	 * folder so WordPress replaces dv-visual rather than installing a duplicate.
	 *
	 * @param string|WP_Error $source        Unpacked source path.
	 * @param string          $remote_source Remote source path.
	 * @param WP_Upgrader     $upgrader      Upgrader instance.
	 * @param array           $hook_extra    Upgrade metadata.
	 * @return string|WP_Error
	 */
	public function normalize_source_directory( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		if ( is_wp_error( $source ) || empty( $hook_extra['theme'] ) || $this->theme_slug !== $hook_extra['theme'] ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return new WP_Error( 'dv_github_filesystem_unavailable', __( 'O WordPress não conseguiu acessar o sistema de arquivos para validar a atualização.', 'dv-visual' ) );
		}

		/*
		 * GitHub can deliver either:
		 * 1) OWNER-REPO-HASH/style.css
		 * 2) OWNER-REPO-HASH/dv-visual/style.css
		 * 3) A custom release ZIP whose first folder is the theme itself.
		 * Locate the real theme root before WordPress replaces the active theme.
		 */
		$candidates = array( untrailingslashit( $source ) );
		$children = $wp_filesystem->dirlist( untrailingslashit( $source ) );
		if ( is_array( $children ) ) {
			foreach ( $children as $name => $info ) {
				if ( isset( $info['type'] ) && 'd' === $info['type'] ) {
					$candidates[] = trailingslashit( untrailingslashit( $source ) ) . $name;
				}
			}
		}

		$theme_root = '';
		foreach ( array_unique( $candidates ) as $candidate ) {
			if ( $this->is_valid_theme_package( $candidate ) ) {
				$theme_root = untrailingslashit( $candidate );
				break;
			}
		}

		if ( '' === $theme_root ) {
			return new WP_Error(
				'dv_github_invalid_theme_package',
				__( 'Atualização cancelada: o pacote publicado no GitHub não contém uma instalação completa do tema DV Visual. A versão atualmente instalada foi preservada.', 'dv-visual' )
			);
		}

		$desired = trailingslashit( $remote_source ) . $this->theme_slug;
		if ( untrailingslashit( $theme_root ) === untrailingslashit( $desired ) ) {
			return trailingslashit( $theme_root );
		}

		if ( $wp_filesystem->exists( $desired ) ) {
			$wp_filesystem->delete( $desired, true );
		}

		if ( ! $wp_filesystem->move( $theme_root, $desired, true ) ) {
			return new WP_Error( 'dv_github_source_move_failed', __( 'Não foi possível preparar o pacote do GitHub para atualização do tema. O tema instalado não foi substituído.', 'dv-visual' ) );
		}

		return trailingslashit( $desired );
	}

	/**
	 * Ensure a downloaded package is really the complete DV Visual theme before
	 * WordPress is allowed to replace the active installation.
	 *
	 * @param string $root Candidate package root.
	 * @return bool
	 */
	private function is_valid_theme_package( $root ) {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return false;
		}

		$root = trailingslashit( $root );
		$required_files = array(
			'style.css',
			'functions.php',
			'front-page.php',
			'header.php',
			'footer.php',
			'inc/github-updater.php',
			'template-parts/pages/home.php',
		);

		foreach ( $required_files as $file ) {
			if ( ! $wp_filesystem->is_file( $root . $file ) ) {
				return false;
			}
		}

		$style = $wp_filesystem->get_contents( $root . 'style.css' );
		if ( false === $style || false === stripos( $style, 'Theme Name: DV Visual' ) ) {
			return false;
		}

		return true;
	}

	public function clear_cache_after_update( $upgrader, $options ) {
		if ( isset( $options['type'] ) && 'theme' === $options['type'] ) {
			delete_site_transient( self::CACHE_KEY );
		}
	}

	public function register_settings() {
		register_setting(
			'dv_visual_github_updates',
			self::OPTION_KEY,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * @param mixed $input Settings input.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_themes' );

		$current = get_option( self::OPTION_KEY, array() );
		$current = is_array( $current ) ? $current : array();
		$token = isset( $current['token'] ) ? sanitize_text_field( $current['token'] ) : '';

		if ( isset( $input['token'] ) ) {
			$token = sanitize_text_field( trim( (string) $input['token'] ) );
		}

		return array(
			'owner'       => self::GITHUB_OWNER,
			'repository'  => self::GITHUB_REPOSITORY,
			'token'       => $token,
			'prereleases' => empty( $input['prereleases'] ) ? 0 : 1,
		);
	}

	public function register_settings_page() {
		add_submenu_page(
			'dv-home-builder',
			__( 'Atualizações via GitHub', 'dv-visual' ),
			__( 'Atualizações GitHub', 'dv-visual' ),
			'update_themes',
			self::SETTINGS_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'update_themes' ) ) {
			return;
		}

		$this->settings = $this->get_settings();
		$release = $this->get_release();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'DV Visual — Atualizações via GitHub', 'dv-visual' ); ?></h1>
			<p><?php esc_html_e( 'Este tema está conectado diretamente ao repositório oficial Diniz-visual/dv-visual. Releases novas aparecerão no atualizador nativo do WordPress.', 'dv-visual' ); ?></p>

			<?php if ( isset( $_GET['dv_checked'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Verificação de atualização concluída.', 'dv-visual' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'dv_visual_github_updates' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dv-gh-owner"><?php esc_html_e( 'Usuário / organização', 'dv-visual' ); ?></label></th>
						<td><code><?php echo esc_html( self::GITHUB_OWNER ); ?></code><input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[owner]" value="<?php echo esc_attr( self::GITHUB_OWNER ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="dv-gh-repository"><?php esc_html_e( 'Repositório', 'dv-visual' ); ?></label></th>
						<td><code><?php echo esc_html( self::GITHUB_REPOSITORY ); ?></code><input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[repository]" value="<?php echo esc_attr( self::GITHUB_REPOSITORY ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="dv-gh-token"><?php esc_html_e( 'Fine-grained token', 'dv-visual' ); ?></label></th>
						<td>
							<input id="dv-gh-token" type="password" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[token]" value="<?php echo esc_attr( (string) $this->settings['token'] ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'Salvo diretamente nas configurações deste tema no WordPress. Para repositório privado, use um Fine-grained Personal Access Token com acesso Read-only ao repositório dv-visual.', 'dv-visual' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Canal', 'dv-visual' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[prereleases]" value="1" <?php checked( ! empty( $this->settings['prereleases'] ) ); ?>> <?php esc_html_e( 'Aceitar prereleases (beta/RC)', 'dv-visual' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button( __( 'Salvar configurações', 'dv-visual' ) ); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Status', 'dv-visual' ); ?></h2>
			<p><strong><?php esc_html_e( 'Versão instalada:', 'dv-visual' ); ?></strong> <?php echo esc_html( $this->theme_version ); ?></p>
			<?php if ( ! $this->is_configured() ) : ?>
				<p><?php esc_html_e( 'Informe o usuário/organização e o repositório para ativar as atualizações.', 'dv-visual' ); ?></p>
			<?php elseif ( is_wp_error( $release ) ) : ?>
				<p style="color:#b32d2e"><strong><?php esc_html_e( 'GitHub:', 'dv-visual' ); ?></strong> <?php echo esc_html( $release->get_error_message() ); ?></p>
			<?php elseif ( is_array( $release ) ) : ?>
				<p><strong><?php esc_html_e( 'Última release:', 'dv-visual' ); ?></strong> <?php echo esc_html( $release['tag'] ); ?> — <?php echo version_compare( $release['version'], $this->theme_version, '>' ) ? esc_html__( 'atualização disponível', 'dv-visual' ) : esc_html__( 'tema atualizado', 'dv-visual' ); ?></p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dv_visual_github_check">
				<?php wp_nonce_field( 'dv_visual_github_check' ); ?>
				<?php submit_button( __( 'Verificar agora', 'dv-visual' ), 'secondary', 'submit', false ); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Como publicar uma atualização', 'dv-visual' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Atualize a versão no style.css e em DINIZ_STUDIO_VERSION.', 'dv-visual' ); ?></li>
				<li><?php esc_html_e( 'Envie as alterações ao GitHub.', 'dv-visual' ); ?></li>
				<li><?php esc_html_e( 'Crie uma tag e uma GitHub Release com a mesma versão, por exemplo v4.29.0.', 'dv-visual' ); ?></li>
				<li><?php esc_html_e( 'O WordPress detectará a release e permitirá instalar pelo atualizador padrão.', 'dv-visual' ); ?></li>
			</ol>
		</div>
		<?php
	}

	public function manual_check() {
		if ( ! current_user_can( 'update_themes' ) ) {
			wp_die( esc_html__( 'Você não tem permissão para verificar atualizações.', 'dv-visual' ) );
		}
		check_admin_referer( 'dv_visual_github_check' );
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_themes' );
		$this->get_release( true );
		wp_update_themes();
		wp_safe_redirect( add_query_arg( 'dv_checked', '1', admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) ) );
		exit;
	}
}

DV_Visual_GitHub_Updater::instance();
