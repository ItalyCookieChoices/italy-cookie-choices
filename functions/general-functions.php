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
