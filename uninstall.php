<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Italy Cookie Choices
 * @author    Enea Overclokk, Andrea Pernici, Andrea Cardinale
 * @license   GPLv2 or later
 * @link      https://github.com/ItalyCookieChoices/italy-cookie-choices
 */

/**
 * If uninstall not called from WordPress, then exit
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( is_multisite() ) {

	global $wpdb;

	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );

	// delete_transient( 'TRANSIENT_NAME' );
	delete_option('italy_cookie_choices');

	if ( $blogs )
		foreach ( $blogs as $blog ) {

			switch_to_blog( $blog[ 'blog_id' ] );

			// delete_transient( 'TRANSIENT_NAME' );
			delete_option('italy_cookie_choices');

			restore_current_blog();

		}

} else {

	delete_option('italy_cookie_choices');

	// delete_transient( 'TRANSIENT_NAME' );
	delete_option('italy_cookie_choices');

}

/**
 * Require multilingual functions
 */
require( plugin_dir_path( __FILE__ ) . 'includes/functions-italy-cookie-choices-lang.php' );
deregister_string( 'Italy Cookie Choices', 'Banner text' );
deregister_string( 'Italy Cookie Choices', 'Banner url' );
deregister_string( 'Italy Cookie Choices', 'Banner slug' );
deregister_string( 'Italy Cookie Choices', 'Banner anchor text' );
deregister_string( 'Italy Cookie Choices', 'Banner button text' );
deregister_string( 'Italy Cookie Choices', 'Content message text' );
deregister_string( 'Italy Cookie Choices', 'Content message button text' );