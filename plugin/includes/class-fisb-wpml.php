<?php
/**
 * Integració configurable amb WPML.
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FISB_WPML {

	/**
	 * Retorna cert si WPML està actiu al lloc.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) && function_exists( 'icl_object_id' );
	}

	/**
	 * Codi d'idioma d'un adjunt (p.ex. 'ca', 'es', 'en').
	 *
	 * @param int $attachment_id ID de l'adjunt.
	 * @return string
	 */
	public static function get_language( $attachment_id ) {
		if ( ! self::is_active() ) {
			return '';
		}

		$details = apply_filters( 'wpml_post_language_details', null, $attachment_id );

		return ( is_array( $details ) && ! empty( $details['language_code'] ) ) ? $details['language_code'] : '';
	}

	/**
	 * Llista d'idiomes actius configurats a WPML.
	 *
	 * @return array
	 */
	public static function get_languages() {
		if ( ! self::is_active() ) {
			return array();
		}

		return apply_filters( 'wpml_active_languages', array() );
	}

	/**
	 * Sincronitza ALT i Títol amb totes les traduccions de l'adjunt.
	 * Només té efecte si WPML té "Media Translation" activada per a
	 * aquest element (és a dir, existeixen adjunts traduïts separats).
	 *
	 * @param int    $attachment_id ID de l'adjunt origen.
	 * @param string $alt           Nou text ALT.
	 * @param string $title         Nou títol.
	 * @return int Nombre de traduccions actualitzades.
	 */
	public static function sync( $attachment_id, $alt, $title ) {
		if ( ! self::is_active() ) {
			return 0;
		}

		$trid = apply_filters( 'wpml_element_trid', null, $attachment_id, 'post_attachment' );
		if ( ! $trid ) {
			return 0;
		}

		$translations = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_attachment' );
		if ( empty( $translations ) ) {
			return 0;
		}

		$updated = 0;

		foreach ( $translations as $translation ) {
			if ( empty( $translation->element_id ) || (int) $translation->element_id === (int) $attachment_id ) {
				continue;
			}

			update_post_meta( $translation->element_id, '_wp_attachment_image_alt', $alt );

			wp_update_post(
				array(
					'ID'         => $translation->element_id,
					'post_title' => $title,
				)
			);

			++$updated;
		}

		return $updated;
	}
}
