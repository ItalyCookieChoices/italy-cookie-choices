<?php
/**
 * Bootstrap file
 *
 * Bootstrap the plugin.
 *
 * @link [URL]
 * @since 2.5.0
 *
 * @package Italy_Cookie_Choices
 */

require( __DIR__ . '/vendor/overclokk/minimum-requirements/minimum-requirements.php' );

/**
 * Instantiate the class
 *
 * @param string $php_ver The minimum PHP version.
 * @param string $wp_ver  The minimum WP version.
 * @param string $name    The name of the theme/plugin to check.
 * @param array  $plugins Required plugins format plugin_path/plugin_name.
 *
 * @var Minimum_Requirements
 */
$requirements = new Minimum_Requirements( '5.3', '3.5' );

/**
 * Check compatibility on install
 * If is not compatible on install print an admin_notice
 */
register_activation_hook( __FILE__, array( $requirements, 'check_compatibility_on_install' ) );

/**
 * If it is already installed and activated check if example new version is compatible, if is not don't load plugin code and prin admin_notice
 * This part need more test
 */
if ( ! $requirements->is_compatible_version() ) {

	add_action( 'admin_notices', array( $requirements, 'load_plugin_admin_notices' ) );
	return;

}

/**
 * Require PHP autoload
 */
require( __DIR__ . '/vendor/autoload.php' );

/**
 * Load general function before init
 */
require( __DIR__ . '/functions/general-functions.php' );

/**
 * Required multilingual functions
 */
require( ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'includes/functions-italy-cookie-choices-lang.php' );

add_action( 'init', function () {

	load_plugin_textdomain( 'italy-cookie-choices', false, dirname( ITALY_COOKIE_CHOICES_BASENAME ) . '/lang' );
}, 100 );

add_action( 'plugins_loaded', function () {

	require( ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'init.php' );
	require( ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'init-admin.php' );
}, 11 );
