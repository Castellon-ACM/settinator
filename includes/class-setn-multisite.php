<?php
/**
 * Multisite toggle logic: enable (wp-config + .htaccess + network tables), disable (revert).
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Multisite
 */
class Setn_Multisite {

	/**
	 * Enable multisite: write wp-config constants, .htaccess rules, create network tables, then redirect to network.php.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function enable() {
		$ok = Setn_Wpconfig::enable_multisite_full();
		if ( $ok && Setn_Htaccess::is_writable() ) {
			Setn_Htaccess::add_multisite_rules();
		}
		if ( ! $ok ) {
			return false;
		}
		self::run_network_install_in_current_request();
		return true;
	}

	/**
	 * Disable multisite: remove constants from wp-config and restore single-site .htaccess.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function disable() {
		$ok = Setn_Wpconfig::disable_multisite_full();
		if ( $ok && Setn_Htaccess::is_writable() ) {
			Setn_Htaccess::restore_single_site_rules();
		}
		return $ok;
	}

	/**
	 * Create network tables (wp_site, wp_blogs, etc.) in the current request.
	 * Called right after writing wp-config and .htaccess so tables exist before redirect.
	 *
	 * @return void
	 */
	public static function run_network_install_in_current_request() {
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
		$email = get_option( 'admin_email' );
		$name  = get_option( 'blogname' );
		populate_network( 1, $domain, $email, $name, $path, false );
	}

	/**
	 * Run network install when user lands on setn_install_network=1 (fallback redirect flow).
	 *
	 * @return bool True if handled (redirect sent), false if not this request.
	 */
	public static function run_network_install_on_redirect() {
		if ( ! isset( $_GET['setn_install_network'] ) || '1' !== $_GET['setn_install_network'] ) {
			return false;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'setn_install_network' ) ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( ! defined( 'MULTISITE' ) || ! MULTISITE ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'                => Setn_Settings::PAGE_SLUG,
						'tab'                 => 'general',
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
		populate_network( 1, $domain, $email, $name, $path, false );
		wp_safe_redirect( admin_url( 'network.php' ) );
		exit;
	}
}
