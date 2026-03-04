<?php
/**
 * wp-config.php editor: path, content, validation, save, render tab.
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Wpconfig
 */
class Setn_Wpconfig {

	/**
	 * Tab slug for the wp-config editor.
	 *
	 * @var string
	 */
	const TAB_SLUG = 'wpconfig';

	/**
	 * Nonce action for the wp-config form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'setn_save_wpconfig';

	/**
	 * Get the path to the wp-config.php file (WordPress root).
	 *
	 * @return string
	 */
	public static function get_path() {
		return ABSPATH . 'wp-config.php';
	}

	/**
	 * Read wp-config.php content. Returns empty string if file does not exist or is unreadable.
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
	 * Get last modified time of wp-config.php (Unix timestamp, or null if not exist/unreadable).
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
	 * Check if wp-config.php is writable.
	 *
	 * @return bool
	 */
	public static function is_writable() {
		$path = self::get_path();
		return file_exists( $path ) && is_writable( $path );
	}

	/**
	 * Validate wp-config.php PHP syntax (no null bytes, valid PHP).
	 *
	 * @param string $content Content to validate.
	 * @return bool True if syntax appears valid, false otherwise.
	 */
	public static function validate_syntax( $content ) {
		if ( false !== strpos( $content, "\0" ) ) {
			return false;
		}
		if ( function_exists( 'exec' ) ) {
			$tmp = wp_tempnam( 'wpconfig-' );
			if ( $tmp ) {
				$written = file_put_contents( $tmp, $content, LOCK_EX );
				if ( false !== $written ) {
					$out = array();
					$ret = -1;
					@exec( 'php -l ' . escapeshellarg( $tmp ) . ' 2>&1', $out, $ret );
					@unlink( $tmp );
					if ( 0 === $ret ) {
						return true;
					}
				} else {
					@unlink( $tmp );
				}
			}
		}
		return self::validate_syntax_basic( $content );
	}

	/**
	 * Basic validation when php -l is not available (balanced braces, has PHP open tag).
	 * Ignores brackets inside single/double quoted strings.
	 *
	 * @param string $content Content to validate.
	 * @return bool True if basic checks pass, false otherwise.
	 */
	protected static function validate_syntax_basic( $content ) {
		if ( false === strpos( $content, '<?php' ) && false === strpos( $content, '<?' ) ) {
			return false;
		}
		$stack  = array();
		$pairs  = array( '{' => '}', '[' => ']', '(' => ')' );
		$close  = array_flip( $pairs );
		$len    = strlen( $content );
		$in_str = null;
		$i      = 0;
		while ( $i < $len ) {
			$c = $content[ $i ];
			if ( null === $in_str ) {
				if ( "'" === $c || '"' === $c ) {
					$in_str = $c;
					$i++;
					continue;
				}
				if ( isset( $pairs[ $c ] ) ) {
					$stack[] = $pairs[ $c ];
				} elseif ( isset( $close[ $c ] ) ) {
					if ( empty( $stack ) || array_pop( $stack ) !== $c ) {
						return false;
					}
				}
			} else {
				if ( '\\' === $c && $i + 1 < $len ) {
					$i += 2;
					continue;
				}
				if ( $c === $in_str ) {
					$in_str = null;
				}
			}
			$i++;
		}
		return empty( $stack );
	}

	/**
	 * Save wp-config.php content. Called on form submit.
	 *
	 * @return void
	 */
	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['setn_wpconfig_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setn_wpconfig_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		$content = isset( $_POST['setn_wpconfig_content'] ) ? wp_unslash( $_POST['setn_wpconfig_content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path   = self::get_path();

		if ( ! self::validate_syntax( $content ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'     => Setn_Settings::PAGE_SLUG,
						'tab'      => self::TAB_SLUG,
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
						'page'     => Setn_Settings::PAGE_SLUG,
						'tab'      => self::TAB_SLUG,
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
					'page'     => Setn_Settings::PAGE_SLUG,
					'tab'      => self::TAB_SLUG,
					'setn_err' => 'write_failed',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the wp-config.php editor tab (form + textarea).
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
			<?php esc_html_e( 'Aquí puedes ver y editar el archivo wp-config.php de tu sitio. Está en la raíz de WordPress:', 'settinator' ); ?>
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
			<p><?php esc_html_e( 'Un error en wp-config.php puede dejar tu sitio inaccesible. Haz una copia de seguridad antes de modificar.', 'settinator' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . Setn_Settings::PAGE_SLUG . '&tab=' . self::TAB_SLUG ) ); ?>" id="setn-wpconfig-form">
			<?php wp_nonce_field( self::NONCE_ACTION, 'setn_wpconfig_nonce' ); ?>
			<p>
				<label for="setn_wpconfig_content" class="screen-reader-text"><?php esc_html_e( 'Contenido de wp-config.php', 'settinator' ); ?></label>
				<textarea name="setn_wpconfig_content" id="setn_wpconfig_content" class="large-text code" rows="25" style="width: 100%; font-family: Consolas, Monaco, monospace; font-size: 13px;" <?php echo $writable ? '' : 'readonly'; ?>><?php echo esc_textarea( $content ); ?></textarea>
			</p>
			<?php if ( $writable ) : ?>
				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar wp-config.php', 'settinator' ); ?></button>
				</p>
			<?php endif; ?>
		</form>
		<?php
	}
}
