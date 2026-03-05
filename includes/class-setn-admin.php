<?php
/**
 * Admin tab: options and tools for WordPress admin (Settinator).
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Admin
 */
class Setn_Admin {

	/**
	 * Tab slug for the Admin tab.
	 *
	 * @var string
	 */
	const TAB_SLUG = 'admin';

	/**
	 * Render the Admin tab content.
	 *
	 * @return void
	 */
	public static function render_tab() {
		?>
		<p class="description">
			<?php esc_html_e( 'Opciones y herramientas para el escritorio de WordPress. Aquí podrás añadir ajustes de administración que no pertenecen a General, .htaccess o wp-config.', 'settinator' ); ?>
		</p>
		<div class="card" style="max-width: 600px; padding: 20px;">
			<h2 class="title" style="margin-top: 0;"><?php esc_html_e( 'Admin', 'settinator' ); ?></h2>
			<p><?php esc_html_e( 'Esta pestaña está lista para que añadas aquí las opciones de admin que necesites.', 'settinator' ); ?></p>
		</div>
		<?php
	}
}
