<?php
/**
 * Endpoints AJAX: desat individual i generació automàtica.
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FISB_Ajax {

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
		add_action( 'wp_ajax_fisb_save_field', array( $this, 'save_field' ) );
		add_action( 'wp_ajax_fisb_generate', array( $this, 'generate' ) );
	}

	protected function check() {
		check_ajax_referer( 'fisb_ajax', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sense permisos suficients.', 'fisb' ) ), 403 );
		}
	}

	/**
	 * Desa un únic camp (ALT o Títol) d'un adjunt via AJAX.
	 */
	public function save_field() {
		$this->check();

		$id    = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$field = isset( $_POST['field'] ) ? sanitize_key( $_POST['field'] ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';
		$sync  = ! empty( $_POST['sync'] );

		if ( ! $id || ! in_array( $field, array( 'alt', 'title' ), true ) || 'attachment' !== get_post_type( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Dades no vàlides.', 'fisb' ) ) );
		}

		if ( 'alt' === $field ) {
			update_post_meta( $id, '_wp_attachment_image_alt', $value );
		} else {
			wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => $value,
				)
			);
		}

		$updated = 0;
		if ( $sync ) {
			$alt     = get_post_meta( $id, '_wp_attachment_image_alt', true );
			// Títol cru: get_the_title() passa per `the_title` (wptexturize) i
			// desaria entitats HTML a les traduccions.
			$post    = get_post( $id );
			$title   = $post ? $post->post_title : '';
			$updated = FISB_WPML::sync( $id, $alt, $title );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Desat', 'fisb' ),
				'synced'  => $updated,
			)
		);
	}

	/**
	 * Genera un suggeriment de text ALT segons el mètode indicat.
	 */
	public function generate() {
		$this->check();

		$id     = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$method = isset( $_POST['method'] ) ? sanitize_key( $_POST['method'] ) : 'filename';

		if ( ! $id || 'attachment' !== get_post_type( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'ID no vàlid.', 'fisb' ) ) );
		}

		$template = FISB_Settings::get( 'template', '{post_title}' );
		$value    = FISB_Generator::generate( $id, $method, $template );

		wp_send_json_success( array( 'value' => $value ) );
	}
}
