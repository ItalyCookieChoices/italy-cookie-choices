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

/**
 * The options, the consent log table and its cleanup of the current site.
 */
$icc_uninstall_site = static function (): void {
	global $wpdb;
	/** @var \wpdb $wpdb */

	delete_option( 'italy_cookie_choices' );
	delete_option( 'italy_cookie_choices_version' );
	delete_option( 'italy_cookie_choices_log_table_version' );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}icc_consent_log" ); // phpcs:ignore
	wp_clear_scheduled_hook( 'italy_cookie_choices_consent_log_cleanup' );
};

if ( is_multisite() ) {

	global $wpdb;

	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );

	$icc_uninstall_site();

	if ( $blogs )
		foreach ( $blogs as $blog ) {

			switch_to_blog( $blog[ 'blog_id' ] );

			$icc_uninstall_site();

			restore_current_blog();

		}

} else {

	$icc_uninstall_site();

}

/**
 * Require multilingual functions
 */
require( plugin_dir_path( __FILE__ ) . 'legacy/includes/functions-italy-cookie-choices-lang.php' );
deregister_string( 'Italy Cookie Choices', 'Banner text' );
deregister_string( 'Italy Cookie Choices', 'Banner url' );
deregister_string( 'Italy Cookie Choices', 'Banner slug' );
deregister_string( 'Italy Cookie Choices', 'Banner anchor text' );
deregister_string( 'Italy Cookie Choices', 'Banner button text' );
deregister_string( 'Italy Cookie Choices', 'Content message text' );
deregister_string( 'Italy Cookie Choices', 'Content message button text' );