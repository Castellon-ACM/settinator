<?php
/**
 * Permalink tweaks: category base (remove /category/ from category URLs).
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Permalinks
 */
class Setn_Permalinks {

	/**
	 * Whether the category base is removed (URLs like /nombre-categoria/ instead of /category/nombre-categoria/).
	 *
	 * @return bool
	 */
	public static function is_category_base_removed() {
		return ( get_option( 'category_base' ) === '.' );
	}

	/**
	 * Save category base from General form: remove or restore /category/ prefix.
	 *
	 * @param bool $remove True to remove /category/ from URLs (category_base = '.'), false to use default.
	 * @return void
	 */
	public static function save_category_base( $remove ) {
		if ( $remove ) {
			update_option( 'category_base', '.' );
		} else {
			update_option( 'category_base', 'category' );
		}
		flush_rewrite_rules( false );
	}
}
