<?php
/**
 * Plugin Name: Settinator
 * Description: Plugin con pestaña de ajustes.
 * Version: 1.0.2
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

define( 'SETN_VERSION', '1.0.2' );
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
require_once SETN_PLUGIN_PATH . 'includes/class-setn-multisite.php';
require_once SETN_PLUGIN_PATH . 'includes/class-setn-permalinks.php';
require_once SETN_PLUGIN_PATH . 'includes/class-setn-admin-slug.php';
require_once SETN_PLUGIN_PATH . 'includes/class-setn-admin.php';
require_once SETN_PLUGIN_PATH . 'includes/class-setn-settings.php';

add_action( 'admin_menu', 'setn_add_settings_page' );
add_action( 'admin_init', 'setn_maybe_save_htaccess' );
add_action( 'admin_init', 'setn_maybe_save_wpconfig' );
add_action( 'admin_init', 'setn_maybe_save_general' );
add_action( 'admin_init', 'setn_maybe_save_admin_slug' );
add_action( 'admin_init', 'setn_maybe_run_network_install', 1 );

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

	$remove_category_base = isset( $_POST['setn_remove_category_base'] ) && '1' === $_POST['setn_remove_category_base'];
	Setn_Permalinks::save_category_base( $remove_category_base );

	$enable = isset( $_POST['setn_multisite'] ) && '1' === $_POST['setn_multisite'];
	if ( ! Setn_Wpconfig::is_writable() ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => Setn_Settings::PAGE_SLUG,
					'tab'                 => 'general',
					'setn_err_multisite' => 'not_writable',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	if ( $enable ) {
		$ok = Setn_Multisite::enable();
		if ( $ok ) {
			wp_safe_redirect( admin_url( 'network.php' ) );
			exit;
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => Setn_Settings::PAGE_SLUG,
					'tab'                 => 'general',
					'setn_err_multisite' => 'write_failed',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	// Only run disable when multisite is actually enabled or allowed.
	$was_multisite = ( defined( 'MULTISITE' ) && MULTISITE ) || Setn_Wpconfig::get_multisite_allowed();
	if ( $was_multisite ) {
		$ok = Setn_Multisite::disable();
		if ( $ok ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'              => Setn_Settings::PAGE_SLUG,
						'tab'               => 'general',
						'setn_ok_multisite' => '0',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => Setn_Settings::PAGE_SLUG,
					'tab'                 => 'general',
					'setn_err_multisite' => 'write_failed',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'            => Setn_Settings::PAGE_SLUG,
				'tab'             => 'general',
				'setn_ok_general' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/**
 * Process Admin tab form submit (custom admin URL slug).
 *
 * @return void
 */
function setn_maybe_save_admin_slug() {
	if ( ! isset( $_POST['setn_admin_slug_nonce'] ) || empty( $_POST['setn_admin_slug_nonce'] ) ) {
		return;
	}
	if ( ! isset( $_GET['page'] ) || 'settinator' !== $_GET['page'] || ! isset( $_GET['tab'] ) || 'admin' !== $_GET['tab'] ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setn_admin_slug_nonce'] ) ), Setn_Admin_Slug::NONCE_ACTION ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$raw_slug = isset( $_POST['setn_admin_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['setn_admin_slug'] ) ) : '';
	$valid    = Setn_Admin_Slug::validate_slug( $raw_slug );
	if ( ! $valid['ok'] ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => Setn_Settings::PAGE_SLUG,
					'tab'             => Setn_Admin::TAB_SLUG,
					'setn_err_admin'  => 'invalid',
					'setn_admin_msg'  => rawurlencode( $valid['error'] ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	if ( ! Setn_Admin_Slug::save_slug( $valid['slug'] ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => Setn_Settings::PAGE_SLUG,
					'tab'             => Setn_Admin::TAB_SLUG,
					'setn_err_admin'  => 'not_writable',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	// Log out and send user to the (new) login URL so they access with the new path.
	wp_logout();
	if ( '' !== $valid['slug'] ) {
		$login_url = home_url( '/' . $valid['slug'] . '/' );
	} else {
		$login_url = home_url( '/wp-login.php' );
	}
	$login_url = add_query_arg( 'setn_slug_saved', '1', $login_url );
	wp_safe_redirect( $login_url );
	exit;
}

/**
 * Run network install when redirected with setn_install_network=1 (fallback).
 *
 * @return void
 */
function setn_maybe_run_network_install() {
	Setn_Multisite::run_network_install_on_redirect();
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
