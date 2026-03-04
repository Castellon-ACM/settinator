=== Settinator ===
Contributors: alexcm13
Tags: settings, htaccess, wp-config, configuration, editor
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Edit .htaccess and wp-config.php from the WordPress admin with syntax validation before saving.

== Description ==

Settinator adds its own menu in the WordPress admin from which you can view and edit two critical files of your installation:

* **.htaccess Editor**: edit the root .htaccess file with Apache syntax validation (balanced blocks, RewriteCond, etc.). If syntax is invalid, the file is not saved and "htaccess failed" is shown.
* **wp-config.php Editor**: edit wp-config.php with PHP syntax validation (php -l or basic check). On syntax error, "wp-config failed" is shown.

Both tabs display the file path and **last modified** time so you can confirm that changes were saved correctly.

= Features =

* Dedicated "Settinator" menu in the admin (gear icon).
* General tab (empty by default).
* .htaccess editor with Apache validation and last modified notice.
* wp-config.php editor with PHP validation and last modified notice.
* Content is not saved when syntax is invalid; clear error messages.
* Code organized in separate files (htaccess, wpconfig, settings).

= Requirements =

* WordPress 5.0 or higher.
* PHP 7.4 or higher.
* Write permissions on the WordPress root for .htaccess and on wp-config.php.

== Installation ==

1. Upload the `settinator` folder to `/wp-content/plugins/`.
2. Activate the plugin from the 'Plugins' menu in WordPress.
3. Go to **Settinator** in the admin sidebar.
4. Use the ".htaccess Editor" and "wp-config.php Editor" tabs to edit each file.

== Frequently Asked Questions ==

= Can editing these files break my site? =

Yes. Both .htaccess and wp-config.php are critical files. Always make a backup before modifying. The plugin validates syntax before saving to reduce errors, but does not guarantee that the configuration is correct or secure.

= Why won't it let me save? =

If "htaccess failed" or "wp-config failed" appears, the content you pasted or typed has invalid syntax. Check for unclosed blocks, quotes, parentheses, etc.

== Screenshots ==

1. Settinator menu in the admin.
2. .htaccess Editor tab with content and last modified.
3. wp-config.php Editor tab.

== Changelog ==

= 1.0.1 =
* Code reorganized into separate files (class-setn-htaccess.php, class-setn-wpconfig.php, class-setn-settings.php).
* Added readme.txt and readme.md.

= 1.0.0 =
* Initial release.
* .htaccess editor with Apache syntax validation.
* wp-config.php editor with PHP syntax validation.
* Last modified time shown for each file in its tab.
* Dedicated menu in the admin.

== Upgrade Notice ==

= 1.0.1 =
Improved file structure and documentation (readme). No functional changes.
