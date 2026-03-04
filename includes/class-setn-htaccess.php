<?php
/**
 * .htaccess editor: path, content, validation, save, render tab.
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Htaccess
 */
class Setn_Htaccess {

	/**
	 * Tab slug for the htaccess editor.
	 *
	 * @var string
	 */
	const TAB_SLUG = 'htaccess';

	/**
	 * Nonce action for the htaccess form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'setn_save_htaccess';

	/**
	 * Known Apache block directive names (opening and closing).
	 *
	 * @var string
	 */
	const VALID_TAGS = 'IfModule|IfDefine|Directory|DirectoryMatch|Files|FilesMatch|Location|LocationMatch|VirtualHost|Limit|LimitExcept';

	/**
	 * Get the path to the .htaccess file (WordPress root).
	 *
	 * @return string
	 */
	public static function get_path() {
		return ABSPATH . '.htaccess';
	}

	/**
	 * Read .htaccess content. Returns empty string if file does not exist or is unreadable.
	 *
	 * @return string
	 */
	public static function get_content() {
		$path = self::get_path();
		if ( ! file_exists( $path ) ) {
			return '';
		}
		$content = file_get_contents( $path );
		return false !== $content ? $content : '';
	}

	/**
	 * Get last modified time of .htaccess file (Unix timestamp, or null if not exist/unreadable).
	 *
	 * @return int|null
	 */
	public static function get_last_modified() {
		$path = self::get_path();
		if ( ! file_exists( $path ) ) {
			return null;
		}
		$mtime = filemtime( $path );
		return false !== $mtime ? $mtime : null;
	}

	/**
	 * Check if .htaccess file is writable (or parent dir is writable if file does not exist).
	 *
	 * @return bool
	 */
	public static function is_writable() {
		$path = self::get_path();
		if ( file_exists( $path ) ) {
			return is_writable( $path );
		}
		return is_writable( ABSPATH );
	}

	/**
	 * Replace or add WordPress multisite (subdirectory) rewrite rules in .htaccess.
	 *
	 * @return bool True on success, false if not writable or validation fails.
	 */
	public static function add_multisite_rules() {
		if ( ! self::is_writable() ) {
			return false;
		}
		$content = self::get_content();
		$multisite_block = "\n# BEGIN Multisite\n"
			. "<IfModule mod_rewrite.c>\n"
			. "RewriteEngine On\n"
			. "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n"
			. "RewriteBase /\n"
			. "RewriteRule ^index\\.php$ - [L]\n"
			. "RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]\n"
			. "RewriteCond %{REQUEST_FILENAME} -f [OR]\n"
			. "RewriteCond %{REQUEST_FILENAME} -d\n"
			. "RewriteRule ^ - [L]\n"
			. "RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]\n"
			. "RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\\.php)$ $2 [L]\n"
			. "RewriteRule . index.php [L]\n"
			. "</IfModule>\n"
			. "# END Multisite\n";

		if ( preg_match( '/# BEGIN Multisite\b/', $content ) ) {
			return true;
		}
		if ( preg_match( '/# BEGIN WordPress\b.*?# END WordPress\b/s', $content ) ) {
			$content = preg_replace( '/# BEGIN WordPress\b.*?# END WordPress\b/s', trim( $multisite_block ), $content );
		} else {
			$content = $content . $multisite_block;
		}
		if ( ! self::validate_syntax( $content ) ) {
			return false;
		}
		$result = file_put_contents( self::get_path(), $content, LOCK_EX );
		return false !== $result;
	}

	/**
	 * Replace Multisite block with standard WordPress (single-site) rewrite rules.
	 *
	 * @return bool True on success, false if not writable or validation fails.
	 */
	public static function restore_single_site_rules() {
		if ( ! self::is_writable() ) {
			return false;
		}
		$content = self::get_content();
		$single_block = "\n# BEGIN WordPress\n"
			. "<IfModule mod_rewrite.c>\n"
			. "RewriteEngine On\n"
			. "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n"
			. "RewriteBase /\n"
			. "RewriteRule ^index\\.php$ - [L]\n"
			. "RewriteCond %{REQUEST_FILENAME} -f [OR]\n"
			. "RewriteCond %{REQUEST_FILENAME} -d\n"
			. "RewriteRule ^ - [L]\n"
			. "RewriteRule . index.php [L]\n"
			. "</IfModule>\n"
			. "# END WordPress\n";
		if ( preg_match( '/# BEGIN Multisite\b.*?# END Multisite\b/s', $content ) ) {
			$content = preg_replace( '/# BEGIN Multisite\b.*?# END Multisite\b/s', trim( $single_block ), $content );
		} else {
			return true;
		}
		if ( ! self::validate_syntax( $content ) ) {
			return false;
		}
		$result = file_put_contents( self::get_path(), $content, LOCK_EX );
		return false !== $result;
	}

	/**
	 * Validate .htaccess syntax (balanced directives, invalid patterns, no null bytes).
	 *
	 * @param string $content Content to validate.
	 * @return bool True if syntax appears valid, false otherwise.
	 */
	public static function validate_syntax( $content ) {
		if ( false !== strpos( $content, "\0" ) ) {
			return false;
		}

		$stack = array();
		$regex = '#<(/?)(' . self::VALID_TAGS . ')\b#i';
		if ( preg_match_all( $regex, $content, $m, PREG_SET_ORDER ) ) {
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

		if ( preg_match( '#<(?!\/?(' . self::VALID_TAGS . ')\b)\S#', $content ) ) {
			return false;
		}

		if ( preg_match( '#RewriteCond\b[^\n]*!!#i', $content ) ) {
			return false;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $content );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$before_comment = preg_replace( '/#.*$/', '', $line );
			$before_comment = trim( $before_comment );
			if ( '' === $before_comment ) {
				continue;
			}
			if ( false !== strpos( $before_comment, '//' ) && ! preg_match( '#https?://#', $before_comment ) ) {
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
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['setn_htaccess_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setn_htaccess_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		$content = isset( $_POST['setn_htaccess_content'] ) ? wp_unslash( $_POST['setn_htaccess_content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path   = self::get_path();

		if ( ! self::validate_syntax( $content ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => Setn_Settings::PAGE_SLUG,
						'tab'     => self::TAB_SLUG,
						'setn_err' => 'syntax',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		if ( ! self::is_writable() ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => Setn_Settings::PAGE_SLUG,
						'tab'     => self::TAB_SLUG,
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
						'page'   => Setn_Settings::PAGE_SLUG,
						'tab'    => self::TAB_SLUG,
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
					'page'    => Setn_Settings::PAGE_SLUG,
					'tab'     => self::TAB_SLUG,
					'setn_err' => 'write_failed',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the .htaccess editor tab (form + textarea).
	 *
	 * @return void
	 */
	public static function render_tab() {
		$path       = self::get_path();
		$content    = self::get_content();
		$writable   = self::is_writable();
		$last_mtime = self::get_last_modified();
		?>
		<p class="description">
			<?php esc_html_e( 'Aquí puedes ver y editar el archivo .htaccess de tu sitio. Está en la raíz de WordPress:', 'settinator' ); ?>
			<code><?php echo esc_html( $path ); ?></code>
		</p>
		<?php if ( null !== $last_mtime ) : ?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: date and time of last file modification */
				esc_html__( 'Última modificación del archivo: %s', 'settinator' ),
				esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_mtime ) )
			);
			?>
		</p>
		<?php endif; ?>
		<?php if ( ! $writable ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'El archivo no tiene permisos de escritura. No podrás guardar cambios hasta que ajustes los permisos.', 'settinator' ); ?></p>
			</div>
		<?php endif; ?>
		<div class="notice notice-info inline">
			<p><?php esc_html_e( 'Un error en .htaccess puede dejar tu sitio inaccesible. Haz una copia de seguridad antes de modificar y comprueba la sintaxis de Apache.', 'settinator' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . Setn_Settings::PAGE_SLUG . '&tab=' . self::TAB_SLUG ) ); ?>" id="setn-htaccess-form">
			<?php wp_nonce_field( self::NONCE_ACTION, 'setn_htaccess_nonce' ); ?>
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
