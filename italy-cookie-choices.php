<?php
/**
 * Plugin Name: Italy Cookie Choices (for EU Cookie Law & Cookie Notice)
 * Plugin URI: https://github.com/ItalyCookieChoices/italy-cookie-choices
 * Description: Italy Cookie Choices allows you to easily comply with the european cookie law and block third part cookie in your page.
 * Version: 2.6.0
 * Author: Enea Overclokk, Andrea Pernici, Andrea Cardinale
 * Author URI: https://github.com/ItalyCookieChoices/italy-cookie-choices
 * Text Domain: italy-cookie-choices
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

require( __DIR__ . '/define-constants.php' );

icc_set_plugin_default_constant();

require( __DIR__ . '/bootstrap.php' );

/**
 * Fires once Italy_Cookie_Choices plugin has been loaded.
 *
 * @since 2.5.0
 */
do_action( 'icc_plugin_loaded' );
