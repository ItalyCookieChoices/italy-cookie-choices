<?php
/**
 * Plugin Name: Cookie Choices (for EU Cookie Law & GDPR)
 * Plugin URI: https://github.com/ItalyCookieChoices/italy-cookie-choices
 * Description: Asks the cookie consent by category and blocks the third party scripts, videos and maps until the consent.
 * Version: 3.0.1
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Enea Overclokk, Andrea Pernici, Andrea Cardinale
 * Author URI: https://github.com/ItalyCookieChoices/italy-cookie-choices
 * Text Domain: italy-cookie-choices
 * Domain Path: /languages
 * License: GPLv2 or later
 * Git URI: https://github.com/ItalyCookieChoices/italy-cookie-choices
 * GitHub Plugin URI: https://github.com/ItalyCookieChoices/italy-cookie-choices
 * GitHub Branch: master
 *
 * @package Italy_Cookie_Choices
 * @since 1.0.0
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * This will make shure the plugin files can't be accessed within the web browser directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/legacy/define-constants.php';

icc_set_plugin_default_constant();

require __DIR__ . '/bootstrap.php';

// The daily cleanup of the consent log starts again on the next request after the activation.
register_deactivation_hook( __FILE__, static function (): void {
	wp_clear_scheduled_hook( 'italy_cookie_choices_consent_log_cleanup' );
} );
