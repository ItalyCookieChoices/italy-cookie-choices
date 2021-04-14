<?php
/**
 * General functions file.
 *
 * All general functions are here.
 *
 * @link [URL]
 * @since 2.5.0
 *
 * @package Italy_Cookie_Choices
 */

namespace Italy_Cookie_Choices\Core;

/**
 * Check the current request URI, if we can determine it's probably an XML sitemap, kill loading the widgets
 * @return boolean Return true if is in sitemap.xml (fix for WordPress SEO by Yoast)
 */
function is_sitemaps_xml() {

	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$request_uri = $_SERVER['REQUEST_URI'];
	$extension   = substr( $request_uri, -4 );

	if ( false !== stripos( $request_uri, 'sitemap' ) && ( in_array( $extension, array( '.xml', '.xsl' ) ) ) ) {
		return true;
	}

}

/**
 * Check if User Agent is a Bot
 *
 * Some links with spiders list
 * @link http://www.robotstxt.org/db.html
 * @link http://www.searchenginedictionary.com/spider-names.shtml
 * @link http://www.useragentstring.com/pages/Crawlerlist/
 *
 * @return boolean Return true if User Agent is a Bot
 */
function is_bot() {

	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return false;
	}

	$http_user_agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );

	$bots  = array(
		'googlebot',
		'facebookexternalhit',
		'adsbot-google',
		'google keyword suggestion',
		'facebot',
		'yandexbot',
		'bingbot',
		'ia_archiver',
		'ahrefsbot',
		'ezooms',
		'gslfbot',
		'wbsearchbot',
		'twitterbot',
		'tweetmemebot',
		'twikle',
		'paperlibot',
		'wotbox',
		'unwindfetchor',
		'exabot',
		'mj12bot',
		'yandeximages',
		'turnitinbot',
		'pingdom',
		'Slurp',
		'search.msn.com',
		'nutch',
		'simpy',
		'bot',
		'aspseek',
		'crawler',
		'msnbot',
		'libwww-perl',
		'fast',
		'baidu',
		'googlebot-mobile',
		'adsbot-google-mobile',
		'yahooseeker',
	);

	$bots = apply_filters( 'icc_bots', $bots );

	$bots = strtolower( implode( '|', $bots ) );

	if ( preg_match( '/(' . $bots . ')/i', $http_user_agent ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Function for debugging purpose
 *
 * @param  string $value [description]
 * @return string        [description]
 */
function is_debug() {

	return defined( 'WP_DEBUG' ) && WP_DEBUG;
	// return false;
}

if ( ! function_exists( 'wp_json_encode' ) ) {

	/**
	 * Encode a variable into JSON, with some sanity checks.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $data    Variable (usually an array or object) to encode as JSON.
	 * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
	 * @param int   $depth   Optional. Maximum depth to walk through $data. Must be
	 *                       greater than 0. Default 512.
	 * @return bool|string The JSON encoded string, or false if it cannot be encoded.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {

		/**
		 * json_encode() has had extra params added over the years.
		 * $options was added in 5.3, and $depth in 5.5.
		 * We need to make sure we call it with the correct arguments.
		 */
		if ( version_compare( PHP_VERSION, '5.5', '>=' ) )
			$args = array( $data, $options, $depth );
		elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) )
			$args = array( $data, $options );
		else $args = array( $data );

		$json = call_user_func_array( 'json_encode', $args );

		// If json_encode() was successful, no need to do more sanity checking.
		// ... unless we're in an old version of PHP, and json_encode() returned
		// a string containing 'null'. Then we need to do more sanity checking.
		if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) )
			return $json;

		return call_user_func_array( 'json_encode', $args );
	}
}
