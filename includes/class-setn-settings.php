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
 *
 * Provides two independent editors with their own validators:
 * - .htaccess: Apache syntax (directives, blocks, RewriteCond, etc.).
 * - wp-config.php: PHP syntax (php -l or balanced braces/strings).
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
	 * Nonce action for wp-config form.
	 *
	 * @var string
	 */
	const WPCONFIG_NONCE_ACTION = 'setn_save_wpconfig';

	/*
	 * ==========================================================================
	 * .htaccess: path, content, last modified, writable, validator, save, render
	 * ==========================================================================
	 */

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
	 * Get last modified time of .htaccess file (Unix timestamp, or null if not exist/unreadable).
	 *
	 * @return int|null
	 */
	public static function get_htaccess_last_modified() {
		$path = self::get_htaccess_path();
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
	public static function is_htaccess_writable() {
		$path = self::get_htaccess_path();
		if ( file_exists( $path ) ) {
			return is_writable( $path );
		}
		return is_writable( ABSPATH );
	}

	/*
	 * ==========================================================================
	 * wp-config.php: path, content, last modified, writable, validator, save
	 * ==========================================================================
	 */

	/**
	 * Get the path to the wp-config.php file (WordPress root).
	 *
	 * @return string
	 */
	public static function get_wpconfig_path() {
		return ABSPATH . 'wp-config.php';
	}

	/**
	 * Read wp-config.php content. Returns empty string if file does not exist or is unreadable.
	 *
	 * @return string
	 */
	public static function get_wpconfig_content() {
		$path = self::get_wpconfig_path();
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
	public static function get_wpconfig_last_modified() {
		$path = self::get_wpconfig_path();
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
	public static function is_wpconfig_writable() {
		$path = self::get_wpconfig_path();
		return file_exists( $path ) && is_writable( $path );
	}

	/**
	 * Validator for wp-config.php only. PHP syntax: null bytes, php -l or basic brace/string balance.
	 *
	 * @param string $content Content to validate.
	 * @return bool True if syntax appears valid, false otherwise.
	 */
	public static function validate_wpconfig_syntax( $content ) {
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
		return self::validate_wpconfig_syntax_basic( $content );
	}

	/**
	 * Basic wp-config validation when php -l is not available (balanced braces, has PHP open tag).
	 * Ignores brackets inside single/double quoted strings so define() keys don't break the count.
	 *
	 * @param string $content Content to validate.
	 * @return bool True if basic checks pass, false otherwise.
	 */
	protected static function validate_wpconfig_syntax_basic( $content ) {
		if ( false === strpos( $content, '<?php' ) && false === strpos( $content, '<?' ) ) {
			return false;
		}
		$stack   = array();
		$pairs   = array( '{' => '}', '[' => ']', '(' => ')' );
		$close   = array_flip( $pairs );
		$len     = strlen( $content );
		$in_str  = null; // null = outside, "'" = single quoted, '"' = double quoted.
		$i       = 0;
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
	public static function save_wpconfig() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['setn_wpconfig_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setn_wpconfig_nonce'] ) ), self::WPCONFIG_NONCE_ACTION ) ) {
			return;
		}
		$content = isset( $_POST['setn_wpconfig_content'] ) ? wp_unslash( $_POST['setn_wpconfig_content'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path   = self::get_wpconfig_path();

		if ( ! self::validate_wpconfig_syntax( $content ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'     => self::PAGE_SLUG,
						'tab'      => 'wpconfig',
						'setn_err' => 'syntax',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		if ( ! self::is_wpconfig_writable() ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'     => self::PAGE_SLUG,
						'tab'      => 'wpconfig',
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
						'tab'    => 'wpconfig',
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
					'page'     => self::PAGE_SLUG,
					'tab'      => 'wpconfig',
					'setn_err' => 'write_failed',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Known Apache block directive names (opening and closing). Used only by htaccess validator.
	 *
	 * @var string
	 */
	const HTACCESS_VALID_TAGS = 'IfModule|IfDefine|Directory|DirectoryMatch|Files|FilesMatch|Location|LocationMatch|VirtualHost|Limit|LimitExcept';

	/**
	 * Validator for .htaccess only. Apache syntax: balanced blocks, no invalid <>, no RewriteCond !! or // outside URLs.
	 *
	 * @param string $content Content to validate.
	 * @return bool True if syntax appears valid, false otherwise.
	 */
	public static function validate_htaccess_syntax( $content ) {
		if ( false !== strpos( $content, "\0" ) ) {
			return false;
		}

		// Balanced block directives.
		$stack = array();
		$regex = '#<(/?)(' . self::HTACCESS_VALID_TAGS . ')\b#i';
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

		// Invalid angle bracket: < not followed by valid directive (e.g. <-- in a comment).
		if ( preg_match( '#<(?!\/?(' . self::HTACCESS_VALID_TAGS . ')\b)\S#', $content ) ) {
			return false;
		}

		// RewriteCond with invalid double negation (!!).
		if ( preg_match( '#RewriteCond\b[^\n]*!!#i', $content ) ) {
			return false;
		}

		// Lines with // that are not part of http:// or https:// (invalid in Apache).
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
		// Show admin notices for wp-config save result.
		if ( isset( $_GET['setn_ok'] ) && '1' === $_GET['setn_ok'] && 'wpconfig' === $active_tab ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'El archivo wp-config.php se ha guardado correctamente.', 'settinator' ) . '</p></div>';
		}
		if ( isset( $_GET['setn_err'] ) && 'wpconfig' === $active_tab ) {
			$err = sanitize_key( $_GET['setn_err'] );
			if ( 'syntax' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'wp-config failed', 'settinator' ) . '</p></div>';
			}
			if ( 'not_writable' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'No se puede escribir en wp-config.php. Comprueba los permisos del archivo.', 'settinator' ) . '</p></div>';
			}
			if ( 'write_failed' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Error al guardar el archivo wp-config.php.', 'settinator' ) . '</p></div>';
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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=wpconfig' ) ); ?>"
					class="nav-tab <?php echo 'wpconfig' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Editor wp-config.php', 'settinator' ); ?>
				</a>
			</nav>

			<div class="settinator-tab-content" style="margin-top: 20px;">
				<?php if ( 'general' === $active_tab ) : ?>
					<?php /* Pestaña vacía. */ ?>
				<?php elseif ( 'htaccess' === $active_tab ) : ?>
					<?php self::render_htaccess_tab(); ?>
				<?php elseif ( 'wpconfig' === $active_tab ) : ?>
					<?php self::render_wpconfig_tab(); ?>
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
		$path       = self::get_htaccess_path();
		$content    = self::get_htaccess_content();
		$writable   = self::is_htaccess_writable();
		$last_mtime = self::get_htaccess_last_modified();
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

	/**
	 * Render the wp-config.php editor tab (form + textarea).
	 *
	 * @return void
	 */
	protected static function render_wpconfig_tab() {
		$path       = self::get_wpconfig_path();
		$content    = self::get_wpconfig_content();
		$writable   = self::is_wpconfig_writable();
		$last_mtime = self::get_wpconfig_last_modified();
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

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=wpconfig' ) ); ?>" id="setn-wpconfig-form">
			<?php wp_nonce_field( self::WPCONFIG_NONCE_ACTION, 'setn_wpconfig_nonce' ); ?>
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
