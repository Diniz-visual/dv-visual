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
			'owner'        => '',
			'repository'   => '',
			'token'        => '',
			'prereleases'  => 0,
		);

		$stored = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );
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
		);

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
		$args['headers'] = array_merge( $args['headers'], $this->github_headers() );
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

		$desired = trailingslashit( $remote_source ) . $this->theme_slug . '/';
		if ( untrailingslashit( $source ) === untrailingslashit( $desired ) ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return $source;
		}

		if ( $wp_filesystem->exists( $desired ) ) {
			$wp_filesystem->delete( $desired, true );
		}

		if ( ! $wp_filesystem->move( $source, $desired, true ) ) {
			return new WP_Error( 'dv_github_source_move_failed', __( 'Não foi possível preparar o pacote do GitHub para atualização do tema.', 'dv-visual' ) );
		}

		return $desired;
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

		return array(
			'owner'       => isset( $input['owner'] ) ? sanitize_text_field( $input['owner'] ) : '',
			'repository'  => isset( $input['repository'] ) ? sanitize_text_field( $input['repository'] ) : '',
			'token'       => isset( $input['token'] ) ? sanitize_text_field( $input['token'] ) : '',
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
			<p><?php esc_html_e( 'Conecte este tema a um repositório GitHub. Releases novas aparecerão no atualizador nativo do WordPress.', 'dv-visual' ); ?></p>

			<?php if ( isset( $_GET['dv_checked'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Verificação de atualização concluída.', 'dv-visual' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'dv_visual_github_updates' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dv-gh-owner"><?php esc_html_e( 'Usuário / organização', 'dv-visual' ); ?></label></th>
						<td><input id="dv-gh-owner" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[owner]" value="<?php echo esc_attr( (string) $this->settings['owner'] ); ?>" placeholder="seu-usuario" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"><label for="dv-gh-repository"><?php esc_html_e( 'Repositório', 'dv-visual' ); ?></label></th>
						<td><input id="dv-gh-repository" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[repository]" value="<?php echo esc_attr( (string) $this->settings['repository'] ); ?>" placeholder="dv-visual" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"><label for="dv-gh-token"><?php esc_html_e( 'Token GitHub', 'dv-visual' ); ?></label></th>
						<td>
							<input id="dv-gh-token" type="password" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[token]" value="<?php echo esc_attr( (string) $this->settings['token'] ); ?>" autocomplete="new-password">
							<p class="description"><?php esc_html_e( 'Opcional para repositório público. Para repositório privado, use um fine-grained token vinculado ao proprietário correto, com acesso a este repositório e Repository permissions → Contents: Read-only. Metadata: Read é concedido automaticamente.', 'dv-visual' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Canal', 'dv-visual' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[prereleases]" value="1" <?php checked( ! empty( $this->settings['prereleases'] ) ); ?>> <?php esc_html_e( 'Aceitar prereleases (beta/RC)', 'dv-visual' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button( __( 'Salvar conexão', 'dv-visual' ) ); ?>
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
