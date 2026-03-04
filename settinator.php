<?php
/**
 * Plugin Name: Settinator
 * Description: Plugin con pestaña de ajustes.
 * Version: 1.0.1
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

define( 'SETN_VERSION', '1.0.1' );
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

require_once SETN_PLUGIN_PATH . 'includes/class-setn-htaccess.php';
require_once SETN_PLUGIN_PATH . 'includes/class-setn-wpconfig.php';
require_once SETN_PLUGIN_PATH . 'includes/class-setn-settings.php';

add_action( 'admin_menu', 'setn_add_settings_page' );
add_action( 'admin_init', 'setn_maybe_save_htaccess' );
add_action( 'admin_init', 'setn_maybe_save_wpconfig' );
add_action( 'admin_init', 'setn_maybe_save_general' );

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
	Setn_Htaccess::save();
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
	Setn_Wpconfig::save();
}

/**
 * Process General tab form submit (multisite toggle).
 *
 * @return void
 */
function setn_maybe_save_general() {
	if ( ! isset( $_POST['setn_general_nonce'] ) || empty( $_POST['setn_general_nonce'] ) ) {
		return;
	}
	if ( ! isset( $_GET['page'] ) || 'settinator' !== $_GET['page'] ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setn_general_nonce'] ) ), Setn_Settings::GENERAL_NONCE_ACTION ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$allowed = isset( $_POST['setn_multisite'] ) && '1' === $_POST['setn_multisite'];
	if ( ! Setn_Wpconfig::is_writable() ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => Setn_Settings::PAGE_SLUG,
					'tab'             => 'general',
					'setn_err_multisite' => 'not_writable',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	$ok = Setn_Wpconfig::set_multisite_allowed( $allowed );
	if ( $ok ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => Setn_Settings::PAGE_SLUG,
					'tab'               => 'general',
					'setn_ok_multisite' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'              => Setn_Settings::PAGE_SLUG,
				'tab'               => 'general',
				'setn_err_multisite' => 'write_failed',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
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
