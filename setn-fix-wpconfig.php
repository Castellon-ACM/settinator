<?php
/**
 * One-time script to remove multisite constants from wp-config.php when the site
 * is stuck (multisite enabled but network tables missing). Run from the WordPress ROOT.
 *
 * Usage (choose one):
 *
 * A) From command line (recommended if the browser shows "database connection" error):
 *    cd /path/to/wordpress/root
 *    php setn-fix-wpconfig.php
 *
 * B) From browser:
 *    1. Temporarily rename .htaccess to .htaccess.bak (so the request hits this file).
 *    2. Visit: https://yoursite.com/setn-fix-wpconfig.php?key=setn_disable_multisite
 *    3. Rename .htaccess.bak back to .htaccess.
 *    4. Delete this file.
 *
 * @package Settinator
 */

$key_ok = false;
if ( php_sapi_name() === 'cli' ) {
	$key_ok = true;
} elseif ( isset( $_GET['key'] ) && 'setn_disable_multisite' === $_GET['key'] ) {
	$key_ok = true;
}
if ( ! $key_ok ) {
	die( 'Forbidden. Use ?key=setn_disable_multisite in browser, or run: php setn-fix-wpconfig.php' );
}

$dir = __DIR__;
$wpconfig = $dir . '/wp-config.php';
if ( ! file_exists( $wpconfig ) ) {
	$wpconfig = dirname( $dir ) . '/wp-config.php';
}
if ( ! file_exists( $wpconfig ) ) {
	$wpconfig = dirname( $dir, 3 ) . '/wp-config.php';
}
if ( ! file_exists( $wpconfig ) ) {
	die( 'wp-config.php not found. Run from WordPress root or copy this file there.' );
}
if ( ! is_writable( $wpconfig ) ) {
	die( 'wp-config.php is not writable. Fix permissions and try again.' );
}

$content = file_get_contents( $wpconfig );
if ( false === $content ) {
	die( 'Could not read wp-config.php.' );
}

$constants = array(
	'WP_ALLOW_MULTISITE',
	'MULTISITE',
	'SUBDOMAIN_INSTALL',
	'DOMAIN_CURRENT_SITE',
	'PATH_CURRENT_SITE',
	'SITE_ID_CURRENT_SITE',
	'BLOG_ID_CURRENT_SITE',
);

foreach ( $constants as $const ) {
	$content = preg_replace( '/\s*define\s*\(\s*[\'" ]' . preg_quote( $const, '/' ) . '[\'" ]\s*,\s*[^;]+;\s*\n?/i', "\n", $content );
}

$content = preg_replace( '/\n{3,}/', "\n\n", $content );

if ( false === file_put_contents( $wpconfig, $content, LOCK_EX ) ) {
	die( 'Could not write wp-config.php.' );
}

if ( function_exists( 'opcache_invalidate' ) ) {
	opcache_invalidate( $wpconfig, true );
}

if ( php_sapi_name() === 'cli' ) {
	echo "Done. Multisite constants removed from wp-config.php.\n";
	echo "Reload your site; delete this file after.\n";
	exit;
}

header( 'Content-Type: text/html; charset=utf-8' );
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Settinator fix</title></head><body>';
echo '<h1>Done</h1>';
echo '<p><strong>Multisite constants have been removed from wp-config.php.</strong></p>';
echo '<p>Your site should load as single-site again.</p>';
echo '<p><strong>1.</strong> If you renamed .htaccess, rename it back to .htaccess.</p>';
echo '<p><strong>2.</strong> Delete this file (<code>setn-fix-wpconfig.php</code>) from the server.</p>';
echo '<p><strong>3.</strong> Reload your site.</p>';
echo '<p><strong>4.</strong> To enable multisite again, use Settinator → General and turn on the toggle.</p>';
echo '</body></html>';
