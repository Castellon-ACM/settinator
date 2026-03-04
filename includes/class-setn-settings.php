<?php
/**
 * Settinator Settings page.
 *
 * @package Settinator
 * @author Closemarketing
 * @copyright 2025 Closemarketing
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Settings
 */
class Setn_Settings {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'settinator';

	/**
	 * Nonce action for htaccess form.
	 *
	 * @var string
	 */
	const HTACCESS_NONCE_ACTION = 'setn_save_htaccess';

	/**
	 * Get the path to the .htaccess file (WordPress root).
	 *
	 * @return string
	 */
	public static function get_htaccess_path() {
		return ABSPATH . '.htaccess';
	}

	/**
	 * Read .htaccess content. Returns empty string if file does not exist or is unreadable.
	 *
	 * @return string
	 */
	public static function get_htaccess_content() {
		$path = self::get_htaccess_path();
		if ( ! file_exists( $path ) ) {
			return '';
		}
		$content = file_get_contents( $path );
		return false !== $content ? $content : '';
	}

	/**
	 * Check if .htaccess file is writable (or parent dir is writable if file does not exist).
	 *
	 * @return bool
	 */
	public static function is_htaccess_writable() {
		$path = self::get_htaccess_path();
		if ( file_exists( $path ) ) {
			return is_writable( $path );
		}
		return is_writable( ABSPATH );
	}

	/**
	 * Validate .htaccess syntax (balanced directives, no null bytes).
	 *
	 * @param string $content Content to validate.
	 * @return bool True if syntax appears valid, false otherwise.
	 */
	public static function validate_htaccess_syntax( $content ) {
		if ( false !== strpos( $content, "\0" ) ) {
			return false;
		}
		$stack = array();
		if ( preg_match_all( '#<(/?)(IfModule|IfDefine|Directory|DirectoryMatch|Files|FilesMatch|Location|LocationMatch|VirtualHost|Limit|LimitExcept)\b#i', $content, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $match ) {
				$closed = ( '' !== $match[1] );
				$name   = strtolower( $match[2] );
				if ( $closed ) {
					if ( empty( $stack ) || array_pop( $stack ) !== $name ) {
						return false;
					}
				} else {
					$stack[] = $name;
				}
			}
			if ( ! empty( $stack ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Save .htaccess content. Called on form submit.
	 *
	 * @return void
	 */
	public static function save_htaccess() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['setn_htaccess_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setn_htaccess_nonce'] ) ), self::HTACCESS_NONCE_ACTION ) ) {
			return;
		}
		$content = isset( $_POST['setn_htaccess_content'] ) ? wp_unslash( $_POST['setn_htaccess_content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path   = self::get_htaccess_path();

		if ( ! self::validate_htaccess_syntax( $content ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => self::PAGE_SLUG,
						'tab'     => 'htaccess',
						'setn_err' => 'syntax',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		if ( ! self::is_htaccess_writable() ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => self::PAGE_SLUG,
						'tab'     => 'htaccess',
						'setn_err' => 'not_writable',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		$result = file_put_contents( $path, $content, LOCK_EX );

		if ( false !== $result ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'   => self::PAGE_SLUG,
						'tab'    => 'htaccess',
						'setn_ok' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'tab'     => 'htaccess',
					'setn_err' => 'write_failed',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the settings page with tabs.
	 *
	 * @return void
	 */
	public static function render_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

		// Show admin notices for htaccess save result.
		if ( isset( $_GET['setn_ok'] ) && '1' === $_GET['setn_ok'] && 'htaccess' === $active_tab ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'El archivo .htaccess se ha guardado correctamente.', 'settinator' ) . '</p></div>';
		}
		if ( isset( $_GET['setn_err'] ) && 'htaccess' === $active_tab ) {
			$err = sanitize_key( $_GET['setn_err'] );
			if ( 'syntax' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'htaccess failed', 'settinator' ) . '</p></div>';
			}
			if ( 'not_writable' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'No se puede escribir en .htaccess. Comprueba los permisos del archivo o del directorio raíz.', 'settinator' ) . '</p></div>';
			}
			if ( 'write_failed' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Error al guardar el archivo .htaccess.', 'settinator' ) . '</p></div>';
			}
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Secondary menu', 'settinator' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=general' ) ); ?>"
					class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'settinator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=htaccess' ) ); ?>"
					class="nav-tab <?php echo 'htaccess' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Editor .htaccess', 'settinator' ); ?>
				</a>
			</nav>

			<div class="settinator-tab-content" style="margin-top: 20px;">
				<?php if ( 'general' === $active_tab ) : ?>
					<?php /* Pestaña vacía. */ ?>
				<?php elseif ( 'htaccess' === $active_tab ) : ?>
					<?php self::render_htaccess_tab(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the .htaccess editor tab (form + textarea).
	 *
	 * @return void
	 */
	protected static function render_htaccess_tab() {
		$path     = self::get_htaccess_path();
		$content  = self::get_htaccess_content();
		$writable = self::is_htaccess_writable();
		?>
		<p class="description">
			<?php esc_html_e( 'Aquí puedes ver y editar el archivo .htaccess de tu sitio. Está en la raíz de WordPress:', 'settinator' ); ?>
			<code><?php echo esc_html( $path ); ?></code>
		</p>
		<?php if ( ! $writable ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'El archivo no tiene permisos de escritura. No podrás guardar cambios hasta que ajustes los permisos.', 'settinator' ); ?></p>
			</div>
		<?php endif; ?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'Un error en .htaccess puede dejar tu sitio inaccesible. Haz una copia de seguridad antes de modificar y comprueba la sintaxis de Apache.', 'settinator' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=htaccess' ) ); ?>" id="setn-htaccess-form">
			<?php wp_nonce_field( self::HTACCESS_NONCE_ACTION, 'setn_htaccess_nonce' ); ?>
			<p>
				<label for="setn_htaccess_content" class="screen-reader-text"><?php esc_html_e( 'Contenido de .htaccess', 'settinator' ); ?></label>
				<textarea name="setn_htaccess_content" id="setn_htaccess_content" class="large-text code" rows="20" style="width: 100%; font-family: Consolas, Monaco, monospace; font-size: 13px;" <?php echo $writable ? '' : 'readonly'; ?>><?php echo esc_textarea( $content ); ?></textarea>
			</p>
			<?php if ( $writable ) : ?>
				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar .htaccess', 'settinator' ); ?></button>
				</p>
			<?php endif; ?>
		</form>
		<?php
	}
}
