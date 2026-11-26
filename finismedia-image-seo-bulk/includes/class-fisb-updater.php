<?php
/**
 * Actualitzacions automàtiques des de GitHub Releases.
 *
 * Integra el plugin amb el sistema natiu d'actualitzacions de WordPress:
 * apareix el botó «Actualitza ara» a Plugins i a Escriptori → Actualitzacions.
 *
 * Repositori privat: defineix a wp-config.php
 *     define( 'FISB_GITHUB_TOKEN', 'github_pat_...' ); // permís de lectura de Contents
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FISB_Updater {

	const REPO      = 'mananipons/finismedia-image-seo-bulk';
	const ASSET     = 'finismedia-image-seo-bulk-%s.zip'; // Nom de l'asset de la release.
	const TRANSIENT = 'fisb_github_release';

	/** @var FISB_Updater|null */
	private static $instance = null;

	/** @var string Ex.: finismedia-image-seo-bulk/finismedia-image-seo-bulk.php */
	private $basename;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->basename = plugin_basename( FISB_PATH . 'finismedia-image-seo-bulk.php' );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'authorize_download' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_folder_name' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
	}

	/**
	 * Obté l'última release (cache 6 h). Retorna array normalitzat o null.
	 */
	private function get_release() {
		$cached = get_transient( self::TRANSIENT );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => $this->api_headers( 'application/vnd.github+json' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Cacheja el fracàs 1 h per no martellejar l'API.
			set_transient( self::TRANSIENT, 'error', HOUR_IN_SECONDS );
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['tag_name'] ) ) {
			set_transient( self::TRANSIENT, 'error', HOUR_IN_SECONDS );
			return null;
		}

		$version = ltrim( $data['tag_name'], 'vV' );

		// Busca l'asset zip correcte (no el «Source code» automàtic de GitHub).
		$package = '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( isset( $asset['name'] ) && sprintf( self::ASSET, $version ) === $asset['name'] ) {
					// L'URL d'API permet descarregar també amb repo privat (amb token).
					$package = $asset['url'];
					break;
				}
			}
		}

		if ( '' === $package ) {
			set_transient( self::TRANSIENT, 'error', HOUR_IN_SECONDS );
			return null;
		}

		$release = array(
			'version'  => $version,
			'package'  => $package,
			'url'      => isset( $data['html_url'] ) ? $data['html_url'] : 'https://github.com/' . self::REPO,
			'body'     => isset( $data['body'] ) ? $data['body'] : '',
			'released' => isset( $data['published_at'] ) ? $data['published_at'] : '',
		);

		set_transient( self::TRANSIENT, $release, 6 * HOUR_IN_SECONDS );
		return $release;
	}

	private function api_headers( $accept ) {
		$headers = array(
			'Accept'     => $accept,
			'User-Agent' => 'FISB-Updater',
		);
		if ( defined( 'FISB_GITHUB_TOKEN' ) && FISB_GITHUB_TOKEN ) {
			$headers['Authorization'] = 'Bearer ' . FISB_GITHUB_TOKEN;
		}
		return $headers;
	}

	/**
	 * Injecta la nova versió al transient d'actualitzacions de WordPress.
	 */
	public function check_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_release();
		if ( ! $release || ! version_compare( $release['version'], FISB_VERSION, '>' ) ) {
			return $transient;
		}

		$transient->response[ $this->basename ] = (object) array(
			'slug'        => dirname( $this->basename ),
			'plugin'      => $this->basename,
			'new_version' => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['package'],
		);

		return $transient;
	}

	/**
	 * Dades del popup «Mostra detalls de la versió X».
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( $this->basename ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Finismedia Image SEO Bulk Editor',
			'slug'          => dirname( $this->basename ),
			'version'       => $release['version'],
			'author'        => '<a href="https://finismedia.com">Finismedia</a>',
			'homepage'      => $release['url'],
			'download_link' => $release['package'],
			'last_updated'  => $release['released'],
			'sections'      => array(
				'changelog' => '<pre>' . esc_html( $release['body'] ) . '</pre>',
			),
		);
	}

	/**
	 * Descarrega el paquet nosaltres mateixos: l'API de GitHub necessita
	 * Accept: application/octet-stream (i el token si el repo és privat).
	 */
	public function authorize_download( $reply, $package, $upgrader ) {
		if ( false === strpos( $package, 'api.github.com/repos/' . self::REPO . '/releases/assets/' ) ) {
			return $reply;
		}

		$tmp = wp_tempnam( $package );
		if ( ! $tmp ) {
			return new WP_Error( 'fisb_tmp', __( 'No s\'ha pogut crear el fitxer temporal.', 'fisb' ) );
		}

		$response = wp_remote_get(
			$package,
			array(
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $tmp,
				'headers'  => $this->api_headers( 'application/octet-stream' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new WP_Error( 'fisb_download', __( 'No s\'ha pogut descarregar l\'actualització de GitHub.', 'fisb' ) );
		}

		return $tmp;
	}

	/**
	 * Assegura que la carpeta descomprimida es digui com el slug del plugin.
	 */
	public function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}

		$wanted = trailingslashit( $remote_source ) . dirname( $this->basename ) . '/';
		if ( trailingslashit( $source ) === $wanted ) {
			return $source;
		}

		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->move( untrailingslashit( $source ), untrailingslashit( $wanted ) ) ) {
			return $wanted;
		}

		return $source;
	}

	public function clear_cache( $upgrader, $options ) {
		if ( isset( $options['type'] ) && 'plugin' === $options['type'] ) {
			delete_transient( self::TRANSIENT );
		}
	}
}
