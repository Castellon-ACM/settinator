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
	 * Render the settings page with one empty tab.
	 *
	 * @return void
	 */
	public static function render_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Secondary menu', 'settinator' ); ?>">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG . '&tab=general' ) ); ?>"
					class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'settinator' ); ?>
				</a>
			</nav>

			<div class="settinator-tab-content" style="margin-top: 20px;">
				<?php if ( 'general' === $active_tab ) : ?>
					<?php /* Pestaña vacía. */ ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
