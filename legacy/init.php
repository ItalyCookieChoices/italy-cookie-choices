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

use ItalyStrap\Config\Config;

if ( is_admin() ) {
	return;
}

if ( is_bot() ) {
	return;
}

if ( is_sitemaps_xml() ) {
	return;
}

$options = (array) \get_option( 'italy_cookie_choices' );
$config = new Config( $options );

if ( ! $config->has('active') ) {
	return;
}

if ( 'wp-login.php' === $GLOBALS['pagenow'] ) {
	return;
}

/**
 * accept_button
 * Shortcode per stampare il bottone nella pagina della policy
 * @param  array  $atts    Array con gli attributi dello shortcode.
 * @param  string $content Content of shortcode.
 * @return string          Button per l'accettazione
 */
add_shortcode( 'accept_button', function ( $atts, $content = null ) use ( $config ) {

	$button_text = $config['button_text'] ?? '' ;

	return '<span class="el"><button type="button" data-icc-accept-all>' . esc_attr( $button_text ) . '</button></span>';

} );

/**
 * Display cookie, only for internal use
 * @return string
 */
function _display_cookie() {

	$cookie_list = '<ul>';

	foreach ( $_COOKIE as $key => $val )
		$cookie_list .= '<li>Cooke name: ' . esc_attr( $key ) . ' - val: ' . esc_attr( $val ) . '</li>';

	$cookie_list .= '</ul>';

	return $cookie_list;

}
