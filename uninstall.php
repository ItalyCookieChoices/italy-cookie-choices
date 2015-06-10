<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Italy Cookie Choices
 * @author    Enea Overclokk, Andrea Pernici, Andrea Cardinale
 * @license   GPLv2 or later
 * @link      https://github.com/ItalyCookieChoices/italy-cookie-choices
 * 
 */
/**
 * If uninstall not called from WordPress, then exit
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
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

			//info: remove and optimize tables
			$GLOBALS['wpdb']->query( "DROP TABLE `" . $GLOBALS['wpdb']->prefix . "italy_cookie_choices`");
			$GLOBALS['wpdb']->query( "OPTIMIZE TABLE `" . $GLOBALS['wpdb']->prefix . "options`");

			restore_current_blog();

		}

} else {

	delete_option('italy_cookie_choices');

	// delete_transient( 'TRANSIENT_NAME' );
	delete_option('italy_cookie_choices');

	//info: remove and optimize tables
	$GLOBALS['wpdb']->query("DROP TABLE `".$GLOBALS['wpdb']->prefix."italy_cookie_choices`");
	$GLOBALS['wpdb']->query("OPTIMIZE TABLE `" .$GLOBALS['wpdb']->prefix."options`");

}