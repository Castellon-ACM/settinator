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

/**
 * Add Settinator settings page to admin menu.
 *
 * @return void
 */
function setn_add_settings_page() {
	add_options_page(
		__( 'Settinator', 'settinator' ),
		__( 'Settinator', 'settinator' ),
		'manage_options',
		'settinator',
		array( Setn_Settings::class, 'render_page' )
	);
}
