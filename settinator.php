<?php
/**
 * Plugin Name: Settinator
 * Description: Plugin con pestaña de ajustes.
 * Version: 1.0.0
 * Author: Alejandro Castellón
 * Text Domain: settinator
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package WordPress
 * @author Castellón
 * @copyright 2026 Castellón
 * @license GPL-2.0+
 *
 * @wordpress-plugin
 *
 * Prefix: setn_
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'SETN_VERSION', '1.0.0' );
define( 'SETN_FILE', __FILE__ );
define( 'SETN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SETN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'setn_plugin_init' );

/**
 * Load localization and init plugin.
 *
 * @return void
 */
function setn_plugin_init() {
	load_plugin_textdomain( 'settinator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

require_once SETN_PLUGIN_PATH . 'includes/class-setn-settings.php';

add_action( 'admin_menu', 'setn_add_settings_page' );
add_action( 'admin_init', 'setn_maybe_save_htaccess' );
add_action( 'admin_init', 'setn_maybe_save_wpconfig' );

/**
 * Process .htaccess form submit before rendering the page.
 *
 * @return void
 */
function setn_maybe_save_htaccess() {
	if ( ! isset( $_POST['setn_htaccess_nonce'] ) || empty( $_POST['setn_htaccess_nonce'] ) ) {
		return;
	}
	if ( ! isset( $_GET['page'] ) || 'settinator' !== $_GET['page'] ) {
		return;
	}
	Setn_Settings::save_htaccess();
}

/**
 * Process wp-config.php form submit before rendering the page.
 *
 * @return void
 */
function setn_maybe_save_wpconfig() {
	if ( ! isset( $_POST['setn_wpconfig_nonce'] ) || empty( $_POST['setn_wpconfig_nonce'] ) ) {
		return;
	}
	if ( ! isset( $_GET['page'] ) || 'settinator' !== $_GET['page'] ) {
		return;
	}
	Setn_Settings::save_wpconfig();
}

/**
 * Add Settinator as top-level menu item in admin.
 *
 * @return void
 */
function setn_add_settings_page() {
	add_menu_page(
		__( 'Settinator', 'settinator' ),
		__( 'Settinator', 'settinator' ),
		'manage_options',
		'settinator',
		array( Setn_Settings::class, 'render_page' ),
		'dashicons-admin-generic',
		80
	);
}
