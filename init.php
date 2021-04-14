<?php
/**
 * Init plugin
 *
 * Manage the plugin init
 *
 * @since 2.5.0
 *
 * @package Italy_Cookie_Choices
 */

namespace Italy_Cookie_Choices\Core;

use Overclokk\Cookie\Cookie;

if ( is_admin() ) {
	return;
}

if ( is_bot() ) {
	return;
}

if ( is_sitemaps_xml() ) {
	return;
}

$options = (array) get_option( 'italy_cookie_choices' );

if ( empty( $options['active'] ) ) {
	return;
}

if ( 'wp-login.php' === $GLOBALS['pagenow'] ) {
	return;
}
// if ( isset( $_COOKIE[ $options['cookie_name'] ] ) ) {
// 	return;
// }

$cookie = new Cookie();

// var_dump( $cookie->set( $options['cookie_name'], 'y' ) );
// var_dump( $cookie->get( $options['cookie_name'] ) );
// $cookie->del( $options['cookie_name'] );

/**
 * @todo https://wordpress.org/support/topic/disattivazione-testo-embed-bloccati/
 */

$icc_cookie_choices = new Cookie_Choices( $options, $cookie );
$icc_cookie_choices->run();
