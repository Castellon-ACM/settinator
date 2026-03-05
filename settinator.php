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
require_once SETN_PLUGIN_PATH . 'includes/class-setn-admin.php';
require_once SETN_PLUGIN_PATH . 'includes/class-setn-settings.php';

add_action( 'admin_menu', 'setn_add_settings_page' );
add_action( 'admin_init', 'setn_maybe_save_htaccess' );
add_action( 'admin_init', 'setn_maybe_save_wpconfig' );
add_action( 'admin_init', 'setn_maybe_save_general' );
add_action( 'admin_init', 'setn_maybe_save_admin_slug' );
add_action( 'admin_init', 'setn_maybe_run_network_install', 1 );
add_action( 'plugins_loaded', 'setn_register_admin_url_filters', 5 );

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
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['setn_admin_slug_nonce'] ) ), Setn_Admin::NONCE_ACTION ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$raw_slug = isset( $_POST['setn_admin_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['setn_admin_slug'] ) ) : '';
	$valid    = Setn_Admin::validate_admin_slug( $raw_slug );
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
	update_option( Setn_Admin::OPTION_ADMIN_SLUG, $valid['slug'] );
	if ( ! Setn_Htaccess::add_or_update_admin_slug_rules( $valid['slug'] ) ) {
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
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'          => Setn_Settings::PAGE_SLUG,
				'tab'           => Setn_Admin::TAB_SLUG,
				'setn_ok_admin' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
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
 * Register filters to replace wp-admin with custom slug in admin URLs (when option is set).
 *
 * @return void
 */
function setn_register_admin_url_filters() {
	$slug = get_option( Setn_Admin::OPTION_ADMIN_SLUG, '' );
	if ( '' === $slug || ! is_string( $slug ) ) {
		return;
	}
	add_filter( 'admin_url', 'setn_filter_admin_url', 10, 3 );
	add_filter( 'network_admin_url', 'setn_filter_admin_url', 10, 3 );
	add_filter( 'user_admin_url', 'setn_filter_admin_url', 10, 3 );
}

/**
 * Replace /wp-admin/ with custom slug in admin URLs.
 *
 * @param string $url   Full URL.
 * @param string $path  Path (e.g. admin.php).
 * @param int|null $blog_id Blog ID (unused).
 * @return string
 */
function setn_filter_admin_url( $url, $path, $blog_id = null ) {
	$slug = get_option( Setn_Admin::OPTION_ADMIN_SLUG, '' );
	if ( '' === $slug ) {
		return $url;
	}
	$url = str_replace( '/wp-admin/', '/' . $slug . '/', $url );
	$url = str_replace( '/wp-admin', '/' . $slug, $url );
	return $url;
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
