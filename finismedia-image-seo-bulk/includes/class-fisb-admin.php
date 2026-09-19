<?php
/**
 * Menús i pàgines d'administració.
 *
 * @package Finismedia_Image_SEO_Bulk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FISB_Admin {

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
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function add_menu() {
		add_media_page(
			__( 'SEO Imatges (Finismedia)', 'fisb' ),
			__( 'SEO Imatges', 'fisb' ),
			'upload_files',
			'fisb-images',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'upload.php',
			__( 'Configuració SEO Imatges', 'fisb' ),
			__( 'SEO Imatges - Config', 'fisb' ),
			'manage_options',
			'fisb-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * @param string $hook Hook de la pàgina actual d'admin.
	 */
	public function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'media_page_fisb-images', 'media_page_fisb-settings' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'fisb-admin', FISB_URL . 'assets/admin.css', array(), FISB_VERSION );
		wp_enqueue_script( 'fisb-admin', FISB_URL . 'assets/admin.js', array( 'jquery' ), FISB_VERSION, true );

		wp_localize_script(
			'fisb-admin',
			'FISB',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'fisb_ajax' ),
				'syncMode'   => FISB_Settings::get( 'wpml_sync_mode', 'ask' ),
				'wpmlActive' => FISB_WPML::is_active(),
				'i18n'       => array(
					'error'       => __( 'Error en desar', 'fisb' ),
					'confirmSync' => __( "Vols sincronitzar aquest canvi amb totes les traduccions d'aquesta imatge?", 'fisb' ),
				),
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		$list_table = new FISB_List_Table();
		$list_table->prepare_items();
		?>
		<div class="wrap fisb-wrap">
			<h1><?php esc_html_e( "SEO d'imatges en lot", 'fisb' ); ?></h1>
			<p>
				<?php esc_html_e( 'Edita l\'ALT i el Títol de les imatges de la mediateca. Els canvis d\'un camp es desen automàticament en sortir-ne (AJAX); també pots aplicar accions en lot a la selecció.', 'fisb' ); ?>
			</p>

			<?php if ( FISB_WPML::is_active() ) : ?>
				<p class="fisb-wpml-note">
					<?php
					printf(
						/* translators: 1: mode actual, 2: enllaç a configuració */
						esc_html__( 'WPML detectat. Mode de sincronització actual: %1$s. Es pot canviar a %2$s.', 'fisb' ),
						'<strong>' . esc_html( $this->sync_mode_label() ) . '</strong>',
						'<a href="' . esc_url( admin_url( 'upload.php?page=fisb-settings' ) ) . '">' . esc_html__( 'Configuració', 'fisb' ) . '</a>'
					);
					?>
				</p>
			<?php endif; ?>

			<form method="get">
				<input type="hidden" name="page" value="fisb-images" />
				<?php
				$list_table->search_box( __( 'Cerca', 'fisb' ), 'fisb-search' );
				$list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * @return string
	 */
	protected function sync_mode_label() {
		$labels = array(
			'independent' => __( 'Independent per idioma', 'fisb' ),
			'sync'        => __( 'Sincronitzar amb totes les traduccions', 'fisb' ),
			'ask'         => __( 'Preguntar en cada desat', 'fisb' ),
		);

		$mode = FISB_Settings::get( 'wpml_sync_mode', 'ask' );

		return isset( $labels[ $mode ] ) ? $labels[ $mode ] : $labels['ask'];
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$method   = FISB_Settings::get( 'default_method', 'filename' );
		$template = FISB_Settings::get( 'template', '{post_title}' );
		$sync     = FISB_Settings::get( 'wpml_sync_mode', 'ask' );
		?>
		<div class="wrap fisb-wrap">
			<h1><?php esc_html_e( 'Configuració - SEO Imatges', 'fisb' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'fisb_settings_group' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mètode de generació per defecte', 'fisb' ); ?></th>
						<td>
							<select name="fisb_settings[default_method]">
								<option value="filename" <?php selected( $method, 'filename' ); ?>><?php esc_html_e( 'A partir del nom de fitxer', 'fisb' ); ?></option>
								<option value="context" <?php selected( $method, 'context' ); ?>><?php esc_html_e( 'A partir del context (post/pàgina)', 'fisb' ); ?></option>
								<option value="template" <?php selected( $method, 'template' ); ?>><?php esc_html_e( 'Plantilla personalitzada', 'fisb' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'S\'utilitza com a valor per defecte a l\'acció en lot "aplicar plantilla" i com a referència general.', 'fisb' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Plantilla', 'fisb' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="fisb_settings[template]" value="<?php echo esc_attr( $template ); ?>" placeholder="{post_title} - {filename}" />
							<p class="description"><?php esc_html_e( 'Placeholders disponibles: {filename}, {post_title}, {site_name}', 'fisb' ); ?></p>
						</td>
					</tr>
					<?php if ( FISB_WPML::is_active() ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sincronització amb WPML', 'fisb' ); ?></th>
						<td>
							<select name="fisb_settings[wpml_sync_mode]">
								<option value="independent" <?php selected( $sync, 'independent' ); ?>><?php esc_html_e( 'Independent per idioma (no sincronitzar)', 'fisb' ); ?></option>
								<option value="sync" <?php selected( $sync, 'sync' ); ?>><?php esc_html_e( 'Sincronitzar sempre amb totes les traduccions', 'fisb' ); ?></option>
								<option value="ask" <?php selected( $sync, 'ask' ); ?>><?php esc_html_e( 'Preguntar en cada desat', 'fisb' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Només té efecte quan els mèdia estan traduïts individualment a WPML (Media Translation activada per a imatges).', 'fisb' ); ?></p>
						</td>
					</tr>
					<?php endif; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
