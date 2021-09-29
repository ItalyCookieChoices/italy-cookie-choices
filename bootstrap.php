<?php
declare(strict_types=1);

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
