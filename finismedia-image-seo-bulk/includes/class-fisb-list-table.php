<?php
/**
 * Taula d'edició en lot d'ALT/Títol basada en WP_List_Table.
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class FISB_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'imatge',
				'plural'   => 'imatges',
				'ajax'     => false,
			)
		);
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'thumbnail' => '',
			'filename'  => __( 'Fitxer', 'fisb' ),
			'alt'       => __( 'Text ALT', 'fisb' ),
			'title'     => __( 'Títol', 'fisb' ),
			'used_in'   => __( "S'utilitza a", 'fisb' ),
			'status'    => __( 'Estat', 'fisb' ),
		);

		if ( FISB_WPML::is_active() ) {
			$columns['language'] = __( 'Idioma', 'fisb' );
		}

		return $columns;
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'generate_filename'       => __( 'Generar ALT: a partir del nom de fitxer', 'fisb' ),
			'generate_context'        => __( 'Generar ALT: a partir del context', 'fisb' ),
			'generate_template'       => __( 'Generar ALT: aplicar plantilla (veure camp a dalt)', 'fisb' ),
			'generate_title_filename' => __( 'Generar Títol: a partir del nom de fitxer', 'fisb' ),
			'generate_title_context'  => __( 'Generar Títol: a partir del context', 'fisb' ),
			'generate_title_template' => __( 'Generar Títol: aplicar plantilla (veure camp a dalt)', 'fisb' ),
			'clear_alt'               => __( 'Buidar ALT', 'fisb' ),
			'clear_title'             => __( 'Buidar Títol', 'fisb' ),
		);
	}

	/**
	 * @param array  $item        Fila.
	 * @param string $column_name Nom de columna.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="attachment[]" value="%d" />', (int) $item['ID'] );
	}

	public function column_thumbnail( $item ) {
		return wp_get_attachment_image( $item['ID'], array( 50, 50 ) );
	}

	public function column_filename( $item ) {
		$edit_link = get_edit_post_link( $item['ID'] );
		$filename  = basename( get_attached_file( $item['ID'] ) );

		return sprintf(
			'<strong><a href="%1$s" target="_blank">%2$s</a></strong>
			<div class="row-actions">
				<span class="generate">%4$s: <a href="#" class="fisb-generate" data-id="%3$d" data-field="alt" data-method="filename">%5$s</a> | <a href="#" class="fisb-generate" data-id="%3$d" data-field="alt" data-method="context">%6$s</a></span>
				<span class="generate"> &mdash; %7$s: <a href="#" class="fisb-generate" data-id="%3$d" data-field="title" data-method="filename">%5$s</a> | <a href="#" class="fisb-generate" data-id="%3$d" data-field="title" data-method="context">%6$s</a></span>
			</div>',
			esc_url( $edit_link ? $edit_link : '#' ),
			esc_html( $filename ),
			(int) $item['ID'],
			esc_html__( 'ALT', 'fisb' ),
			esc_html__( 'genera per nom', 'fisb' ),
			esc_html__( 'genera per context', 'fisb' ),
			esc_html__( 'Títol', 'fisb' )
		);
	}

	public function column_alt( $item ) {
		return sprintf(
			'<input type="text" class="fisb-field regular-text" data-id="%d" data-field="alt" value="%s" />',
			(int) $item['ID'],
			esc_attr( $item['alt'] )
		);
	}

	public function column_title( $item ) {
		return sprintf(
			'<input type="text" class="fisb-field regular-text" data-id="%d" data-field="title" value="%s" />',
			(int) $item['ID'],
			esc_attr( $item['title'] )
		);
	}

	public function column_used_in( $item ) {
		if ( empty( $item['used_in'] ) ) {
			return '<em>' . esc_html__( 'No detectat', 'fisb' ) . '</em>';
		}

		$link = $item['used_in_id'] ? get_edit_post_link( $item['used_in_id'] ) : '';

		if ( ! $link ) {
			return esc_html( $item['used_in'] );
		}

		return sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $link ), esc_html( $item['used_in'] ) );
	}

	public function column_status( $item ) {
		if ( empty( $item['alt'] ) && empty( $item['title'] ) ) {
			return '<span class="fisb-badge fisb-badge-critical">' . esc_html__( 'Falta ALT i Títol', 'fisb' ) . '</span>';
		}
		if ( empty( $item['alt'] ) ) {
			return '<span class="fisb-badge fisb-badge-high">' . esc_html__( 'Falta ALT', 'fisb' ) . '</span>';
		}
		if ( empty( $item['title'] ) ) {
			return '<span class="fisb-badge fisb-badge-medium">' . esc_html__( 'Falta Títol', 'fisb' ) . '</span>';
		}
		return '<span class="fisb-badge fisb-badge-ok">' . esc_html__( 'OK', 'fisb' ) . '</span>';
	}

	public function column_language( $item ) {
		$lang = FISB_WPML::get_language( $item['ID'] );
		return $lang ? esc_html( strtoupper( $lang ) ) : '—';
	}

	/**
	 * Fila de filtres addicionals (estat, idioma, plantilla).
	 *
	 * @param string $which 'top' o 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$status = isset( $_REQUEST['fisb_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fisb_status'] ) ) : '';
		$lang   = isset( $_REQUEST['fisb_lang'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fisb_lang'] ) ) : '';
		$tpl    = isset( $_REQUEST['fisb_template'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fisb_template'] ) ) : FISB_Settings::get( 'template', '{post_title}' );
		?>
		<div class="alignleft actions">
			<select name="fisb_status">
				<option value=""><?php esc_html_e( 'Tots els estats', 'fisb' ); ?></option>
				<option value="missing_alt" <?php selected( $status, 'missing_alt' ); ?>><?php esc_html_e( 'Falta ALT', 'fisb' ); ?></option>
				<option value="missing_title" <?php selected( $status, 'missing_title' ); ?>><?php esc_html_e( 'Falta Títol', 'fisb' ); ?></option>
				<option value="missing_any" <?php selected( $status, 'missing_any' ); ?>><?php esc_html_e( 'Falta ALT o Títol', 'fisb' ); ?></option>
			</select>

			<?php if ( FISB_WPML::is_active() ) : ?>
				<select name="fisb_lang">
					<option value=""><?php esc_html_e( 'Tots els idiomes', 'fisb' ); ?></option>
					<?php foreach ( FISB_WPML::get_languages() as $code => $language ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $lang, $code ); ?>>
							<?php echo esc_html( isset( $language['translated_name'] ) ? $language['translated_name'] : $code ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<input type="text" name="fisb_template" class="regular-text" placeholder="{post_title} - {filename}" value="<?php echo esc_attr( $tpl ); ?>" />

			<?php submit_button( __( 'Filtra', 'fisb' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Carrega, filtra i pagina els elements a mostrar.
	 */
	public function prepare_items() {
		$per_page = 50;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$paged  = $this->get_pagenum();
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$status = isset( $_REQUEST['fisb_status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fisb_status'] ) ) : '';
		$lang   = isset( $_REQUEST['fisb_lang'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fisb_lang'] ) ) : '';

		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			's'              => $search,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);

		$query = new WP_Query( $args );

		$items = array();

		foreach ( $query->posts as $post ) {
			$alt   = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
			$title = $post->post_title;

			if ( 'missing_alt' === $status && '' !== $alt ) {
				continue;
			}
			if ( 'missing_title' === $status && '' !== $title ) {
				continue;
			}
			if ( 'missing_any' === $status && '' !== $alt && '' !== $title ) {
				continue;
			}
			if ( $lang && FISB_WPML::is_active() && FISB_WPML::get_language( $post->ID ) !== $lang ) {
				continue;
			}

			$parent_id = wp_get_post_parent_id( $post->ID );
			$used_in   = $parent_id ? get_the_title( $parent_id ) : '';

			if ( ! $used_in ) {
				// Reutilitza la lògica del generador per detectar destacada/contingut.
				$context = FISB_Generator::generate( $post->ID, 'context' );
				if ( $context && $context !== FISB_Generator::from_filename( $post->ID ) ) {
					$used_in = $context;
				}
			}

			$items[] = array(
				'ID'         => $post->ID,
				'alt'        => $alt,
				'title'      => $title,
				'used_in'    => $used_in,
				'used_in_id' => $parent_id,
			);
		}

		$this->items = $items;

		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $query->max_num_pages,
			)
		);
	}

	/**
	 * Nota: quan hi ha filtres d'estat/idioma actius, el recompte de
	 * paginació es basa en el total sense filtrar (limitació coneguda
	 * de la v1, ja que estat i idioma no viuen com a camps de post
	 * directament consultables). Amb col·leccions molt grans, cal
	 * revisar-ho afegint un meta_query dedicat.
	 */

	/**
	 * Processa l'acció en lot seleccionada.
	 */
	public function process_bulk_action() {
		$action = $this->current_action();
		if ( ! $action ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		$ids = isset( $_REQUEST['attachment'] ) ? array_map( 'intval', (array) wp_unslash( $_REQUEST['attachment'] ) ) : array();
		if ( empty( $ids ) ) {
			return;
		}

		$template  = isset( $_REQUEST['fisb_template'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['fisb_template'] ) ) : FISB_Settings::get( 'template', '{post_title}' );
		$sync_mode = FISB_Settings::get( 'wpml_sync_mode', 'ask' );
		$do_sync   = ( 'sync' === $sync_mode );

		foreach ( $ids as $id ) {
			switch ( $action ) {
				case 'generate_filename':
					update_post_meta( $id, '_wp_attachment_image_alt', FISB_Generator::generate( $id, 'filename' ) );
					break;
				case 'generate_context':
					update_post_meta( $id, '_wp_attachment_image_alt', FISB_Generator::generate( $id, 'context' ) );
					break;
				case 'generate_template':
					update_post_meta( $id, '_wp_attachment_image_alt', FISB_Generator::generate( $id, 'template', $template ) );
					break;
				case 'generate_title_filename':
					wp_update_post(
						array(
							'ID'         => $id,
							'post_title' => FISB_Generator::generate( $id, 'filename' ),
						)
					);
					break;
				case 'generate_title_context':
					wp_update_post(
						array(
							'ID'         => $id,
							'post_title' => FISB_Generator::generate( $id, 'context' ),
						)
					);
					break;
				case 'generate_title_template':
					wp_update_post(
						array(
							'ID'         => $id,
							'post_title' => FISB_Generator::generate( $id, 'template', $template ),
						)
					);
					break;
				case 'clear_alt':
					update_post_meta( $id, '_wp_attachment_image_alt', '' );
					break;
				case 'clear_title':
					wp_update_post(
						array(
							'ID'         => $id,
							'post_title' => '',
						)
					);
					break;
				default:
					continue 2;
			}

			if ( $do_sync ) {
				$post = get_post( $id );
				FISB_WPML::sync( $id, get_post_meta( $id, '_wp_attachment_image_alt', true ), $post ? $post->post_title : '' );
			}
		}
	}
}
