<?php
/**
 * Configuració del plugin (Settings API).
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FISB_Settings {

	const OPTION_KEY = 'fisb_settings';

	protected static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Llegeix una opció desada, amb valor per defecte.
	 *
	 * @param string $key     Clau dins de l'array d'opcions.
	 * @param mixed  $default Valor per defecte.
	 * @return mixed
	 */
	public static function get( $key, $default = '' ) {
		$options = get_option( self::OPTION_KEY, array() );
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Registra l'opció a la Settings API de WordPress.
	 */
	public function register_settings() {
		register_setting( 'fisb_settings_group', self::OPTION_KEY, array( $this, 'sanitize' ) );
	}

	/**
	 * Neteja i valida les dades del formulari de configuració.
	 *
	 * @param array $input Dades enviades.
	 * @return array
	 */
	public function sanitize( $input ) {
		$output = array();

		$method              = isset( $input['default_method'] ) ? $input['default_method'] : '';
		$output['default_method'] = in_array( $method, array( 'filename', 'context', 'template' ), true ) ? $method : 'filename';

		$output['template'] = isset( $input['template'] ) ? sanitize_text_field( $input['template'] ) : '{post_title}';

		$sync_mode = isset( $input['wpml_sync_mode'] ) ? $input['wpml_sync_mode'] : '';
		$output['wpml_sync_mode'] = in_array( $sync_mode, array( 'independent', 'sync', 'ask' ), true ) ? $sync_mode : 'ask';

		return $output;
	}
}
