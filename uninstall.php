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
 * The strings registered in WPML, Polylang lists only the strings registered on the current request.
 */
if ( function_exists( 'icl_unregister_string' ) ) {
	$icc_strings = [
		'Banner text',
		'Banner url',
		'Banner slug',
		'Banner anchor text',
		'Banner button text',
		'Content message text',
		'Content message button text',
	];

	foreach ( [ 'necessary', 'preferences', 'statistics', 'marketing' ] as $icc_category ) {
		$icc_strings[] = "Category {$icc_category} label";
		$icc_strings[] = "Category {$icc_category} description";
	}

	foreach ( $icc_strings as $icc_string ) {
		icl_unregister_string( 'Italy Cookie Choices', $icc_string );
	}
}
