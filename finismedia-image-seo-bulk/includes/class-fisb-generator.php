<?php
/**
 * Generació automàtica de text ALT/Títol per a imatges.
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FISB_Generator {

	/**
	 * Genera un text net a partir del nom de fitxer de l'adjunt.
	 *
	 * @param int $attachment_id ID de l'adjunt.
	 * @return string
	 */
	public static function from_filename( $attachment_id ) {
		$file = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$name = pathinfo( $file, PATHINFO_FILENAME );

		// Elimina sufixos de mida generats per WordPress (-300x200).
		$name = preg_replace( '/-\d+x\d+$/', '', $name );
		// Elimina IDs/hash numèrics llargs al final del nom.
		$name = preg_replace( '/-[0-9]{4,}$/', '', $name );

		$name = str_replace( array( '-', '_' ), ' ', $name );
		$name = preg_replace( '/\s+/', ' ', trim( $name ) );

		return $name ? ucfirst( $name ) : '';
	}

	/**
	 * Genera un text a partir del context on s'utilitza la imatge
	 * (post/pàgina pare, imatge destacada, o primera referència al contingut).
	 *
	 * @param int $attachment_id ID de l'adjunt.
	 * @return string
	 */
	public static function from_context( $attachment_id ) {
		$title = self::context_title( $attachment_id );

		if ( $title ) {
			return $title;
		}

		// Sense context detectat: recorre al nom de fitxer.
		return self::from_filename( $attachment_id );
	}

	/**
	 * Aplica una plantilla amb placeholders {filename}, {post_title}, {site_name}.
	 *
	 * @param int    $attachment_id ID de l'adjunt.
	 * @param string $template      Plantilla amb placeholders.
	 * @return string
	 */
	public static function from_template( $attachment_id, $template ) {
		if ( '' === trim( (string) $template ) ) {
			$template = '{post_title}';
		}

		$replacements = array(
			'{filename}'   => self::from_filename( $attachment_id ),
			'{post_title}' => self::context_title( $attachment_id ),
			'{site_name}'  => get_bloginfo( 'name' ),
		);

		$result = str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
		$result = preg_replace( '/\s+/', ' ', trim( $result ) );
		$result = trim( $result, " -–|" );

		return $result;
	}

	/**
	 * Punt d'entrada únic segons el mètode triat.
	 *
	 * @param int    $attachment_id ID de l'adjunt.
	 * @param string $method        'filename' | 'context' | 'template'.
	 * @param string $template      Plantilla (només per 'template').
	 * @return string
	 */
	public static function generate( $attachment_id, $method, $template = '' ) {
		switch ( $method ) {
			case 'context':
				return self::from_context( $attachment_id );
			case 'template':
				return self::from_template( $attachment_id, $template );
			case 'filename':
			default:
				return self::from_filename( $attachment_id );
		}
	}

	/**
	 * Troba el títol del post/pàgina que dona context a la imatge.
	 *
	 * @param int $attachment_id ID de l'adjunt.
	 * @return string
	 */
	protected static function context_title( $attachment_id ) {
		$parent_id = wp_get_post_parent_id( $attachment_id );

		if ( ! $parent_id ) {
			$parent_id = self::find_referencing_post( $attachment_id );
		}

		if ( ! $parent_id ) {
			return '';
		}

		// Títol cru (sense passar per `the_title`): wptexturize/idioma hi
		// afegeixen entitats HTML (&#8217;) i cometes tipogràfiques («»)
		// pensades per imprimir-se sense escapar, no per desar-les tal
		// qual a un altre post_title.
		$parent = get_post( $parent_id );

		return $parent ? $parent->post_title : '';
	}

	/**
	 * Cerca el primer post que fa servir la imatge com a destacada
	 * o referenciada dins del contingut (bloc/HTML amb classe wp-image-{ID}).
	 *
	 * @param int $attachment_id ID de l'adjunt.
	 * @return int
	 */
	protected static function find_referencing_post( $attachment_id ) {
		global $wpdb;

		// 1. Imatge destacada d'algun post.
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d LIMIT 1",
				$attachment_id
			)
		);
		if ( $post_id ) {
			return (int) $post_id;
		}

		// 2. Referenciada dins del contingut de l'editor.
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s LIMIT 1",
				'%wp-image-' . (int) $attachment_id . '%'
			)
		);

		return $post_id ? (int) $post_id : 0;
	}
}
