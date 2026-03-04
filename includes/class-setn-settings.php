<?php
/**
 * Settinator settings page: menu, tabs shell, admin notices.
 *
 * Delegates .htaccess and wp-config editing to Setn_Htaccess and Setn_Wpconfig.
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
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
					<?php /* Pestaña vacía. */ ?>
				<?php elseif ( Setn_Htaccess::TAB_SLUG === $active_tab ) : ?>
					<?php Setn_Htaccess::render_tab(); ?>
				<?php elseif ( Setn_Wpconfig::TAB_SLUG === $active_tab ) : ?>
					<?php Setn_Wpconfig::render_tab(); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
