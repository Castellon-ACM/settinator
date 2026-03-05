<?php
/**
 * Settinator settings page: menu, tabs shell, admin notices.
 *
 * Delegates .htaccess and wp-config editing to Setn_Htaccess and Setn_Wpconfig.
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.2
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
	 * Nonce action for the General tab (multisite toggle) form.
	 *
	 * @var string
	 */
	const GENERAL_NONCE_ACTION = 'setn_save_general';

	/**
	 * Render the settings page with tabs.
	 *
	 * @return void
	 */
	public static function render_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

		// Notices for htaccess save result.
		if ( isset( $_GET['setn_ok'] ) && '1' === $_GET['setn_ok'] && Setn_Htaccess::TAB_SLUG === $active_tab ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'El archivo .htaccess se ha guardado correctamente.', 'settinator' ) . '</p></div>';
		}
		if ( isset( $_GET['setn_err'] ) && Setn_Htaccess::TAB_SLUG === $active_tab ) {
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

		// Notices for General (multisite / category base) save result.
		if ( isset( $_GET['setn_ok_general'] ) && '1' === $_GET['setn_ok_general'] && 'general' === $active_tab ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cambios guardados.', 'settinator' ) . '</p></div>';
		}
		if ( isset( $_GET['setn_ok_multisite'] ) && 'general' === $active_tab ) {
			if ( '1' === $_GET['setn_ok_multisite'] ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Multisite enabled. Redirecting to Network Admin.', 'settinator' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Reverted to single-site. wp-config.php and .htaccess have been restored.', 'settinator' ) . '</p></div>';
			}
		}
		if ( isset( $_GET['setn_err_multisite'] ) && 'general' === $active_tab ) {
			$err = sanitize_key( $_GET['setn_err_multisite'] );
			if ( 'not_writable' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'wp-config.php is not writable. Cannot update multisite setting.', 'settinator' ) . '</p></div>';
			}
			if ( 'write_failed' === $err ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Failed to update wp-config.php.', 'settinator' ) . '</p></div>';
			}
			if ( 'config' === $err ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'Multisite config was not loaded yet (e.g. cache). Try activating the toggle again and when redirected, wait for the next page to finish loading.', 'settinator' ) . '</p></div>';
			}
		}

		// Notices for wp-config save result.
		if ( isset( $_GET['setn_ok'] ) && '1' === $_GET['setn_ok'] && Setn_Wpconfig::TAB_SLUG === $active_tab ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'El archivo wp-config.php se ha guardado correctamente.', 'settinator' ) . '</p></div>';
		}
		if ( isset( $_GET['setn_err'] ) && Setn_Wpconfig::TAB_SLUG === $active_tab ) {
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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . Setn_Htaccess::TAB_SLUG ) ); ?>"
					class="nav-tab <?php echo Setn_Htaccess::TAB_SLUG === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Editor .htaccess', 'settinator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . Setn_Wpconfig::TAB_SLUG ) ); ?>"
					class="nav-tab <?php echo Setn_Wpconfig::TAB_SLUG === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Editor wp-config.php', 'settinator' ); ?>
				</a>
			</nav>

			<div class="settinator-tab-content" style="margin-top: 20px;">
				<?php if ( 'general' === $active_tab ) : ?>
					<?php self::render_general_tab(); ?>
				<?php elseif ( Setn_Htaccess::TAB_SLUG === $active_tab ) : ?>
					<?php Setn_Htaccess::render_tab(); ?>
				<?php elseif ( Setn_Wpconfig::TAB_SLUG === $active_tab ) : ?>
					<?php Setn_Wpconfig::render_tab(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the General tab (multisite toggle).
	 *
	 * @return void
	 */
	public static function render_general_tab() {
		$is_multisite          = defined( 'MULTISITE' ) && MULTISITE;
		$multisite_allowed     = Setn_Wpconfig::get_multisite_allowed();
		$wpconfig_writable     = Setn_Wpconfig::is_writable();
		$network_setup_url     = admin_url( 'network.php' );
		$network_setup_new     = admin_url( 'network/setup.php' );
		$category_base_removed = Setn_Permalinks::is_category_base_removed();
		?>
		<style>
			.setn-toggle-wrap { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
			.setn-toggle-wrap .toggle { position: relative; width: 50px; height: 26px; background: #ccc; border-radius: 26px; cursor: pointer; }
			.setn-toggle-wrap .toggle::after { content: ''; position: absolute; width: 22px; height: 22px; border-radius: 50%; background: #fff; top: 2px; left: 2px; transition: left 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
			.setn-toggle-wrap input { position: absolute; opacity: 0; width: 0; height: 0; }
			.setn-toggle-wrap input:checked + .toggle { background: #2271b1; }
			.setn-toggle-wrap input:checked + .toggle::after { left: 26px; }
			.setn-toggle-wrap input:disabled + .toggle { opacity: 0.6; cursor: not-allowed; }
		</style>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=general' ) ); ?>" id="setn-general-form">
			<?php wp_nonce_field( self::GENERAL_NONCE_ACTION, 'setn_general_nonce' ); ?>
			<div class="setn-toggle-wrap" style="margin-bottom: 16px;">
				<label class="setn-toggle-label" style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
					<input type="checkbox" name="setn_remove_category_base" value="1" <?php checked( $category_base_removed ); ?>>
					<span class="toggle" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Quitar /category/ de las URLs de categorías', 'settinator' ); ?></span>
				</label>
			</div>
			<p class="description" style="margin-top: -8px; margin-bottom: 20px;"><?php esc_html_e( 'Si está activo, las categorías usarán URLs como /nombre-categoria/ en lugar de /category/nombre-categoria/.', 'settinator' ); ?></p>
			<div class="setn-toggle-wrap" style="margin-bottom: 16px;">
				<label class="setn-toggle-label" style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
					<input type="checkbox" name="setn_multisite" value="1" <?php checked( $multisite_allowed || $is_multisite ); ?> <?php echo $wpconfig_writable ? '' : 'disabled'; ?>>
					<span class="toggle" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Enable multisite (WP_ALLOW_MULTISITE)', 'settinator' ); ?></span>
				</label>
			</div>
			<?php if ( $is_multisite ) : ?>
				<p class="description"><?php esc_html_e( 'Multisite is active. Uncheck the toggle and save to revert to single-site (wp-config and .htaccess will be restored).', 'settinator' ); ?></p>
				<p><a href="<?php echo esc_url( $network_setup_url ); ?>" class="button"><?php esc_html_e( 'Network Admin', 'settinator' ); ?></a></p>
			<?php elseif ( ! $wpconfig_writable ) : ?>
				<p class="description" style="color: #b32d2e;"><?php esc_html_e( 'wp-config.php is not writable. Make it writable to change this setting.', 'settinator' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'When enabled, Settinator will add all multisite constants to wp-config.php, update .htaccess, and create the network. The site will become multisite (subdirectory install).', 'settinator' ); ?></p>
			<?php endif; ?>
			<?php if ( $wpconfig_writable ) : ?>
				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'settinator' ); ?></button>
				</p>
			<?php endif; ?>
		</form>
		<?php if ( $multisite_allowed && ! $is_multisite ) : ?>
			<div class="notice notice-warning inline" style="margin-top: 24px; padding: 16px 20px; display: block;">
				<p style="margin: 0 0 12px 0; font-weight: 600;"><?php esc_html_e( 'The site is not multisite yet.', 'settinator' ); ?></p>
				<p style="margin: 0 0 12px 0;"><?php esc_html_e( 'WP_ALLOW_MULTISITE is enabled in wp-config.php, but you must complete the Network Setup so that WordPress adds the rest of the configuration and converts this site to multisite.', 'settinator' ); ?></p>
				<p style="margin: 0;">
					<a href="<?php echo esc_url( $network_setup_new ); ?>" class="button button-primary button-hero"><?php esc_html_e( 'Go to Network Setup (Tools → Network Setup)', 'settinator' ); ?></a>
				</p>
			</div>
		<?php endif; ?>
		<?php
	}
}
