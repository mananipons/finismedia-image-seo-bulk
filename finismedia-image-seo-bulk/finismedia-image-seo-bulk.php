<?php
/**
 * Plugin Name: Finismedia Image SEO Bulk Editor
 * Plugin URI:  https://finismedia.com
 * Description: Edita en lot els atributs ALT i Títol de les imatges de la mediateca, amb generació automàtica opcional i compatibilitat configurable amb WPML.
 * Version:     1.2.0
 * Author:      Finismedia
 * Author URI:  https://finismedia.com
 * Text Domain: fisb
 * License:     GPL v2 or later
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FISB_VERSION', '1.2.0' );
define( 'FISB_PATH', plugin_dir_path( __FILE__ ) );
define( 'FISB_URL', plugin_dir_url( __FILE__ ) );

require_once FISB_PATH . 'includes/class-fisb-generator.php';
require_once FISB_PATH . 'includes/class-fisb-wpml.php';
require_once FISB_PATH . 'includes/class-fisb-settings.php';
require_once FISB_PATH . 'includes/class-fisb-list-table.php';
require_once FISB_PATH . 'includes/class-fisb-admin.php';
require_once FISB_PATH . 'includes/class-fisb-ajax.php';

/**
 * Arrenca el plugin un cop tots els plugins estan carregats
 * (necessari perquè la detecció de WPML sigui fiable).
 */
function fisb_init() {
	FISB_Settings::instance();
	FISB_Admin::instance();
	FISB_Ajax::instance();
}
add_action( 'plugins_loaded', 'fisb_init' );
