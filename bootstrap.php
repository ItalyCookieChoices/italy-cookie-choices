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













/**
 * Italy Cookie Choices Class for showing Cookie Banner and block thirdy party script
 */
class Italy_Cookie_Choices{

	/**
	 * Minimum requirement PHP
	 * @var string
	 */
	private $PHP_ver = '5.3';

	/**
	 * Minimum requirement WordPress
	 * @var string
	 */
	private $WP_ver = '3.5';

	/**
	 * Make some magics
	 */
	public function __construct() {

		/**
		 * Check if is compatible and then instantiate it
		 */
		if ( $this->is_compatible_version() && is_admin() ) {

			new Italy_Cookie_Choices\Admin\Settings;

		} else if (

			! $this->_is_bot()

			// If is compatible.
			&& $this->is_compatible_version()

			// Only if is not admin.
			&& ! is_admin()

			// If is not sitemaps.xml by Yoast.
			&& ! $this->is_sitemaps_xml()

			) {

			if ( function_exists( 'pll__' ) )// Compatibility with Polylang.
				add_action( 'plugins_loaded', array( $this, 'dependency_init' ), 11 );
			else new Italy_Cookie_Choices\Core\Cookie_Choices;

		} else add_action( 'admin_notices', array( $this, 'load_plugin_admin_notices' ) );

		/**
		 * Check compatibility on install
		 */
		register_activation_hook( __FILE__, array( $this, 'check_compatibility_on_install' ) );

		/**
		 * Adjust priority to make sure this runs
		 */
		add_action( 'init', array( $this, 'init' ), 100 );

	}

	/**
	 * Check if plugin is compatible, if it is not then it wont activate
	 * Show error message in case plugin is not compatible
	 */
	public function check_compatibility_on_install() {

		if ( ! $this->is_compatible_version() ) {

			$HTML  = '<div>' . __( 'Activation of Italy Cookie Choices in not possible', 'italy-cookie-choices' ) . ':</div><ul>';

			if ( ! $this->is_compatible_php() )
				$HTML .= '<li>' . $this->get_admin_notices_php( false ) . '</li>';

			if ( ! $this->is_compatible_wordpress() )
				$HTML .= '<li>' . $this->get_admin_notices_wordpress( false ) . '</li>';

			$HTML .= '</ul>';

			wp_die( $HTML, __( 'Activation of Italy Cookie Choices in not possible', 'italy-cookie-choices' ), array( 'back_link' => true ) );
		};
	}

	/**
	 * Init functions
	 */
	public function init() {

		/**
		 * Load Lang file
		 */
		load_plugin_textdomain( 'italy-cookie-choices', false, dirname( ITALY_COOKIE_CHOICES_BASENAME ) . '/lang' );

	}

	/**
	 * For Polylang compatibility
	 * Istantiate Class
	 */
	public function dependency_init() {

		new Italy_Cookie_Choices\Core\Cookie_Choices;

	}

	/**
	 * Checking compatibility with installed versions of the plugin
	 * In case of incompatibility still fully loaded plugin (return)
	 * @return boolean Check if plugin is compatible
	 */
	public function is_compatible_version() {

		if ( $this->is_compatible_php() && $this->is_compatible_wordpress() )
			return true;
		else return false;

	}

	/**
	 * Checking the compatibility of the plugin with the version of PHP
	 * In case of incompatibility still fully loaded plugin (return)
	 * @return boolean Check PHP compatibility
	 */
	public function is_compatible_php() {

		if ( version_compare( phpversion(), $this->PHP_ver, '<' ) )
			return false;
		else return true;

	}

	/**
	 * Checking the compatibility of the plugin with the version of Wordpress
	 * In case of incompatibility still fully loaded plugin (return)
	 * @return boolean Check WordPress compatibility
	 */
	public function is_compatible_wordpress() {

		if ( version_compare( $GLOBALS['wp_version'], $this->WP_ver, '<' ) )
			return false;
		else return true;

	}

	/**
	 * If the plugin is active, but the minimum requirements are not met
	 * the function is called to add the details on the notice board error
	 * Print error message
	 */
	public function load_plugin_admin_notices() {

		if ( ! $this->is_compatible_php() )
			echo $this->get_admin_notices_php( true );

		if ( ! $this->is_compatible_wordpress() )
			echo $this->get_admin_notices_wordpress( true );
	}

	/**
	 * Get the admin notice PHP
	 * @param  bolean $wrap
	 * @return string       Return the admin notice for PHP
	 */
	public function get_admin_notices_php( $wrap ) {

		return $this->get_admin_notices_text( $wrap, 'PHP', phpversion(), $this->PHP_ver );
	}

	/**
	 * Get the admin notice WordPress
	 * @param  bolean $wrap
	 * @return string       Return the admin notice for WordPress
	 */
	public function get_admin_notices_wordpress( $wrap ) {

		return $this->get_admin_notices_text( $wrap, 'WordPress', $GLOBALS['wp_version'], $this->WP_ver );
	}

	/**
	 * A function that creates a generic error to be displayed during
	 * the activation function or on the bulletin board of directors.
	 * @param  bolean $wrap
	 * @param  string $s1   PHP or WordPress.
	 * @param  string $s2   Current version.
	 * @param  string $s3   Required version.
	 * @return string       Display errors
	 */
	public function get_admin_notices_text( $wrap, $s1, $s2, $s3 ) {

		$HTML = __( 'Your server is running %s version %s but this plugin requires at least %s', 'italy-cookie-choices' );

		if ( false === $wrap )
			$HTML = '<div>' . $HTML . '</div>';

		else $HTML = '<div class="error"><p><b>Italy Cookie Choices</b> - ' . $HTML . '</p></div>';

		return sprintf( $HTML, $s1, $s2, $s3 );
	}


	/**
	 * Check the current request URI, if we can determine it's probably an XML sitemap, kill loading the widgets
	 * @return boolean Return true if is in sitemap.xml (fix for WordPress SEO by Yoast)
	 */
	public function is_sitemaps_xml() {

		if ( ! isset( $_SERVER['REQUEST_URI'] ) )
			return;

		$request_uri = $_SERVER['REQUEST_URI'];
		$extension   = substr( $request_uri, -4 );

		if ( false !== stripos( $request_uri, 'sitemap' ) && ( in_array( $extension, array( '.xml', '.xsl' ) ) ) )
			return true;

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
	private function _is_bot() {

		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) )
			return false;

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

		$bots = strtolower( implode( '|', $bots ) );

		if ( preg_match( '/(' . $bots . ')/i', $http_user_agent ) ) return true;

		else return false;

	}
} // End Italy_Cookie_Choices

// new Italy_Cookie_Choices;
