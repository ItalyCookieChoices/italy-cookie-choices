<?php
/**
 * Set default constants
 *
 * @package Italy_Cookie_Choices
 * @since   2.5.0
 */

/**
 * Set default constant for the plugin.
 */
function icc_set_plugin_default_constant() {

	/**
	 * Define some costant for internal use
	 */
	if ( ! defined( 'ITALY_COOKIE_CHOICES_PLUGIN' ) ) {
		define( 'ITALY_COOKIE_CHOICES_PLUGIN', true );
	}

	/**
	 * Example = F:\xampp\htdocs\italystrap\wp-content\plugins\italystrap-extended\italystrap.php
	 */
	if ( ! defined( 'ITALY_COOKIE_CHOICES_FILE' ) ) {
		define( 'ITALY_COOKIE_CHOICES_FILE', __FILE__ );
	}

	/**
	 * Example = F:\xampp\htdocs\italystrap\wp-content\plugins\italystrap-extended/
	 */
	if ( ! defined( 'ITALY_COOKIE_CHOICES_PLUGIN_PATH' ) ) {
		define( 'ITALY_COOKIE_CHOICES_PLUGIN_PATH', plugin_dir_path( ITALY_COOKIE_CHOICES_FILE ) );
	}
	/**
	 * Example = italystrap-extended/italystrap.php
	 */
	if ( ! defined( 'ITALY_COOKIE_CHOICES_BASENAME' ) ) {
		define( 'ITALY_COOKIE_CHOICES_BASENAME', plugin_basename( ITALY_COOKIE_CHOICES_FILE ) );
	}

	/**
	 * Example = F:\xampp\htdocs\italystrap\wp-content\plugins\italy-cookie-choices
	 */
	if ( ! defined( 'ITALY_COOKIE_CHOICES_DIRNAME' ) ) {
		define( 'ITALY_COOKIE_CHOICES_DIRNAME', dirname( ITALY_COOKIE_CHOICES_FILE ) );
	}

}
