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
		$ok = Setn_Wpconfig::enable_multisite_full();
		if ( $ok && Setn_Htaccess::is_writable() ) {
			Setn_Htaccess::add_multisite_rules();
		}
		if ( $ok ) {
			setn_run_network_install_in_current_request();
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
	$ok = Setn_Wpconfig::disable_multisite_full();
	if ( $ok && Setn_Htaccess::is_writable() ) {
		Setn_Htaccess::restore_single_site_rules();
	}
	if ( $ok ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => Setn_Settings::PAGE_SLUG,
					'tab'                 => 'general',
					'setn_ok_multisite'   => '0',
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

/**
 * Create network tables (wp_site, wp_blogs, etc.) in the current request.
 * Called right after writing wp-config and .htaccess so tables exist before any redirect.
 *
 * @return void
 */
function setn_run_network_install_in_current_request() {
	global $wpdb;
	$domain = parse_url( home_url(), PHP_URL_HOST );
	$path   = parse_url( home_url(), PHP_URL_PATH );
	if ( empty( $domain ) ) {
		$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : 'localhost';
	}
	if ( empty( $path ) ) {
		$path = '/';
	}
	$path = rtrim( $path, '/' );
	if ( '' === $path ) {
		$path = '/';
	}
	if ( ! defined( 'MULTISITE' ) ) {
		define( 'MULTISITE', true );
	}
	if ( ! defined( 'SUBDOMAIN_INSTALL' ) ) {
		define( 'SUBDOMAIN_INSTALL', false );
	}
	if ( ! defined( 'DOMAIN_CURRENT_SITE' ) ) {
		define( 'DOMAIN_CURRENT_SITE', $domain );
	}
	if ( ! defined( 'PATH_CURRENT_SITE' ) ) {
		define( 'PATH_CURRENT_SITE', $path );
	}
	if ( ! defined( 'SITE_ID_CURRENT_SITE' ) ) {
		define( 'SITE_ID_CURRENT_SITE', 1 );
	}
	if ( ! defined( 'BLOG_ID_CURRENT_SITE' ) ) {
		define( 'BLOG_ID_CURRENT_SITE', 1 );
	}
	foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
		$wpdb->$table = $prefixed_table;
	}
	require_once ABSPATH . 'wp-admin/includes/network.php';
	if ( network_domain_check() ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	install_network();
	$email  = get_option( 'admin_email' );
	$name   = get_option( 'blogname' );
	populate_network( 1, $domain, $email, $name, $path, false );
}

/**
 * Run network install when redirected (fallback if old redirect is used).
 *
 * @return void
 */
function setn_maybe_run_network_install() {
	if ( ! isset( $_GET['setn_install_network'] ) || '1' !== $_GET['setn_install_network'] ) {
		return;
	}
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'setn_install_network' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! defined( 'MULTISITE' ) || ! MULTISITE ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => Setn_Settings::PAGE_SLUG,
					'tab'   => 'general',
					'setn_err_multisite' => 'config',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	require_once ABSPATH . 'wp-admin/includes/network.php';
	global $wpdb;
	foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
		$wpdb->$table = $prefixed_table;
	}
	if ( network_domain_check() ) {
		wp_safe_redirect( admin_url( 'network.php' ) );
		exit;
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	install_network();
	$domain = defined( 'DOMAIN_CURRENT_SITE' ) ? DOMAIN_CURRENT_SITE : parse_url( home_url(), PHP_URL_HOST );
	$path   = defined( 'PATH_CURRENT_SITE' ) ? PATH_CURRENT_SITE : '/';
	$email  = get_option( 'admin_email' );
	$name   = get_option( 'blogname' );
	$result = populate_network( 1, $domain, $email, $name, $path, false );
	wp_safe_redirect( admin_url( 'network.php' ) );
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
