<?php
/**
 * Custom admin URL (hide /wp-admin/ behind a configurable slug).
 *
 * Handles option, validation, .htaccess rules and admin_url filters.
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Admin_Slug
 */
class Setn_Admin_Slug {

	/**
	 * Option name for the custom admin URL slug.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'setn_admin_slug';

	/**
	 * Nonce action for the admin slug form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'setn_save_admin_slug';

	/**
	 * Reserved slugs that cannot be used as custom admin path.
	 *
	 * @var string[]
	 */
	const RESERVED_SLUGS = array( 'wp-admin', 'wp-login', 'wp-login.php', 'wp-content', 'wp-includes', 'admin', 'feed', 'index.php', 'settinator' );

	/**
	 * Register filters and hooks. Called on plugins_loaded.
	 *
	 * @return void
	 */
	public static function init() {
		$slug = self::get_slug();
		if ( '' !== $slug ) {
			add_filter( 'admin_url', array( __CLASS__, 'filter_admin_url' ), 10, 3 );
			add_filter( 'network_admin_url', array( __CLASS__, 'filter_admin_url' ), 10, 3 );
			add_filter( 'user_admin_url', array( __CLASS__, 'filter_admin_url' ), 10, 3 );
			add_filter( 'login_url', array( __CLASS__, 'filter_login_url' ), 10, 3 );
			add_filter( 'logout_url', array( __CLASS__, 'filter_logout_url' ), 10, 2 );
			add_filter( 'register_url', array( __CLASS__, 'filter_register_url' ), 10, 1 );
			add_filter( 'lostpassword_url', array( __CLASS__, 'filter_lostpassword_url' ), 10, 2 );
		}
	}

	/**
	 * Get the current custom admin slug (empty string = use default wp-admin).
	 *
	 * @return string
	 */
	public static function get_slug() {
		$slug = get_option( self::OPTION_NAME, '' );
		return is_string( $slug ) ? $slug : '';
	}

	/**
	 * Sanitize slug: trim, lowercase, replace spaces and invalid chars with hyphens.
	 *
	 * @param string $slug Raw input.
	 * @return string Slug with only [a-z0-9_-].
	 */
	public static function sanitize_slug( $slug ) {
		$slug = is_string( $slug ) ? trim( $slug ) : '';
		if ( '' === $slug ) {
			return '';
		}
		$slug = strtolower( $slug );
		$slug = preg_replace( '/[\s]+/', '-', $slug );
		$slug = preg_replace( '/[^a-z0-9_-]+/', '-', $slug );
		$slug = preg_replace( '/-+/', '-', $slug );
		$slug = trim( $slug, '-' );
		return $slug;
	}

	/**
	 * Validate and sanitize a candidate admin slug.
	 *
	 * @param string $slug Raw input (e.g. "settinator login" becomes "settinator-login").
	 * @return array{ ok: bool, slug: string, error: string } ok and slug if valid, error message otherwise.
	 */
	public static function validate_slug( $slug ) {
		$slug = self::sanitize_slug( $slug );
		if ( '' === $slug ) {
			return array( 'ok' => true, 'slug' => '', 'error' => '' );
		}
		if ( in_array( $slug, self::RESERVED_SLUGS, true ) ) {
			return array(
				'ok'    => false,
				'slug'  => '',
				'error' => __( 'Ese slug está reservado. Elige otro.', 'settinator' ),
			);
		}
		return array( 'ok' => true, 'slug' => $slug, 'error' => '' );
	}

	/**
	 * Save slug: update option and .htaccess rules.
	 *
	 * @param string $slug Sanitized slug, or empty to revert to wp-admin.
	 * @return bool True on success, false if .htaccess could not be written.
	 */
	public static function save_slug( $slug ) {
		update_option( self::OPTION_NAME, is_string( $slug ) ? $slug : '' );
		return Setn_Htaccess::add_or_update_admin_slug_rules( $slug );
	}

	/**
	 * Replace /wp-admin/ with custom slug in admin URLs.
	 *
	 * @param string   $url     Full URL.
	 * @param string   $path    Path (e.g. admin.php).
	 * @param int|null $blog_id Blog ID (unused).
	 * @return string
	 */
	public static function filter_admin_url( $url, $path, $blog_id = null ) {
		$slug = self::get_slug();
		if ( '' === $slug ) {
			return $url;
		}
		$url = str_replace( '/wp-admin/', '/' . $slug . '/', $url );
		$url = str_replace( '/wp-admin', '/' . $slug, $url );
		return $url;
	}

	/**
	 * Replace wp-login.php with custom slug login URL.
	 *
	 * @param string $url    Login URL.
	 * @param string $redirect Redirect URL.
	 * @param bool   $force_reauth Force reauth.
	 * @return string
	 */
	public static function filter_login_url( $url, $redirect = '', $force_reauth = false ) {
		$slug = self::get_slug();
		if ( '' === $slug ) {
			return $url;
		}
		$login_path = '/' . $slug . '/login';
		$new_url   = set_url_scheme( home_url( $login_path ), is_ssl() ? 'https' : 'http' );
		if ( '' !== $redirect ) {
			$new_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $new_url );
		}
		if ( $force_reauth ) {
			$new_url = add_query_arg( 'reauth', '1', $new_url );
		}
		return $new_url;
	}

	/**
	 * Replace wp-login.php?action=logout with custom slug login URL for logout.
	 *
	 * @param string $url    Logout URL.
	 * @param string $redirect Redirect after logout.
	 * @return string
	 */
	public static function filter_logout_url( $url, $redirect = '' ) {
		$slug = self::get_slug();
		if ( '' === $slug ) {
			return $url;
		}
		$logout_path = '/' . $slug . '/login';
		$new_url    = set_url_scheme( home_url( $logout_path ), is_ssl() ? 'https' : 'http' );
		$new_url    = add_query_arg( 'action', 'logout', $new_url );
		if ( '' !== $redirect ) {
			$new_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $new_url );
		}
		return wp_nonce_url( $new_url, 'log-out' );
	}

	/**
	 * Replace register URL with custom slug path.
	 *
	 * @param string $url Register URL.
	 * @return string
	 */
	public static function filter_register_url( $url ) {
		$slug = self::get_slug();
		if ( '' === $slug ) {
			return $url;
		}
		$path = '/' . $slug . '/login';
		return add_query_arg( 'action', 'register', set_url_scheme( home_url( $path ), is_ssl() ? 'https' : 'http' ) );
	}

	/**
	 * Replace lost password URL with custom slug path.
	 *
	 * @param string $url      Lost password URL.
	 * @param string $redirect Redirect after reset.
	 * @return string
	 */
	public static function filter_lostpassword_url( $url, $redirect = '' ) {
		$slug = self::get_slug();
		if ( '' === $slug ) {
			return $url;
		}
		$path   = '/' . $slug . '/login';
		$new_url = add_query_arg( 'action', 'lostpassword', set_url_scheme( home_url( $path ), is_ssl() ? 'https' : 'http' ) );
		if ( '' !== $redirect ) {
			$new_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $new_url );
		}
		return $new_url;
	}
}

add_action( 'plugins_loaded', array( 'Setn_Admin_Slug', 'init' ), 5 );
