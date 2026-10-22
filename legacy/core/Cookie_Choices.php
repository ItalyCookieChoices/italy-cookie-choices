<?php

namespace Italy_Cookie_Choices\Core;

use ItalyStrap\Config\ConfigInterface;
use Overclokk\Cookie\Cookie;

class Cookie_Choices {

	const SEPARATOR = '<---------SOMETHING--------->';

	/**
	 * Default Cookie name
	 * @var string
	 */
	private $cookieName = 'displayCookieConsent';

	/**
	 * Default cookie value
	 * @var string
	 */
	private $cookieVal = 'y';

	/**
	 * Pattern for searching embed code in content and widget
	 * @var string
	 */
	private $pattern = '#<iframe.*?\/iframe>|<embed.*?>|<script.*?\/script>#is';

	/**
	 * Snippet for replacements
	 * @var string
	 */
	private $valore = '';

	/**
	 * Array with embed found
	 * @var array
	 */
	public $js_array = [];

	/**
	 * Array with list of allowed script|iframe|embed
	 * @var array
	 */
	private $allow_script = [];

	/**
	 * Array with list of blocked script|iframe|embed
	 * @var array
	 */
	private $block_script = [];

	public function __construct(
		private ConfigInterface $options,
		private readonly Cookie $cookie
	) {
	}


	public function run(){

//		d( $this->cookie->get( $this->options->get('cookie_name') ) );
//		$this->cookie->delete( $this->options->get('cookie_name') );

		if ( ! isset( $_COOKIE[ $this->options['cookie_name'] ] ) ){

			$this->disable_w3tc_page_cache();

			/**
			 * Background color for banner
			 * @var string
			 */
			$banner_bg = $this->options['banner_bg'] ?? '#ffffff' ;

			/**
			 * Color for text
			 * @var string
			 */
			$banner_text_color = $this->options['banner_text_color'] ?? '#000000' ;

			/**
			 * Text for banner
			 * @var string
			 */
			$text = $this->options['text'] ?? '' ;

			/**
			 * Text for buttom
			 * @var [type]
			 */
			$button_text = $this->options['button_text'] ?? '' ;

			/**
			 * Checkbox for third part cookie in content
			 * @var bol
			 */
			$block = $this->options['block'] ?? '' ;

			/**
			 * Checkbox for third part cookie in widget
			 * @var bol
			 */
			$widget_block = $this->options['widget_block'] ?? '' ;

			/**
			 * Checkbox for third part cookie in all page (except head and footer)
			 * @var bol
			 */
			$all_block = $this->options['all_block'] ?? '' ;

			/**
			 * Checkbox custom scripts block in BODY
			 * @var string
			 */
			$custom_script_block_body_exclude = $this->options['custom_script_block_body_exclude'] ?? '' ;

			$this->get_blocked_script();
			$this->get_allowed_script();

			/**
			 * Checkbox custom scripts block in HEAD and FOOTER
			 * @var string
			 */
			$custom_script_block = $this->options['custom_script_block'] ?? '' ;

			/**
			 * Text to put inside locked post and widget contents
			 * including the button text
			 * @var string
			 */
			$content_message_text = ( isset( $this->options['content_message_text'] ) ) ? wp_kses_post( get_string( 'Italy Cookie Choices', 'Content message text', $this->options['content_message_text'] ) ) : '' ;

			/**
			 * Text for button in locked content and widget
			 * @var string
			 */
			$content_message_button_text = ( isset( $this->options['content_message_button_text'] ) ) ? esc_attr( get_string( 'Italy Cookie Choices', 'Content message button text', $this->options['content_message_button_text'] ) ) : '' ;

			/**
			 * Replacement for regex
			 * @var string
			 */
			$this->valore = sprintf(
				'<div class="el"><div style="padding:10px;margin-bottom: 18px;color:%1$s;background-color:%2$s;text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);">%3$s&nbsp;&nbsp;<button onclick="cookieChoices.removeCookieConsent()" style="color:%1$s;padding: 3px;font-size: 12px;line-height: 12px;text-decoration: none;text-transform: uppercase;margin:0;display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;  cursor: pointer;  border: 1px solid %1$s;background: rgba(255, 255, 255, 0.03);">%4$s</button></div><cookie></div>',
				esc_attr( $banner_text_color ),
				esc_attr( $banner_bg ),
				$content_message_text,
				$content_message_button_text
			);

			if ( $block ) {
				add_filter( 'the_content', [ $this, 'autoErase'], 11 );
			}

			if ( $widget_block ) {
				add_filter( 'widget_display_callback', [ $this, 'widgetErase'], 11, 3 );
			}

			if ( $all_block ) {
				//add_action('wp_footer', array( $this, 'catchBody' ), -1000000);
				add_action('wp_head', [ $this, 'bufferBodyStart' ], 1000000);
				add_action('wp_footer', [ $this, 'bufferBodyEnd' ], -1000000);
			}

			if( ( $custom_script_block || $this->block_script ) && $all_block ) {
				add_action('template_redirect', [ $this, 'bufferHeadStart' ], 2);
				add_action('wp_head', [ $this, 'bufferHeadEnd' ], 99999);
				add_action('wp_footer', [ $this, 'bufferFooterStart' ], -99998);
				add_action('shutdown', [ $this, 'bufferFooterEnd' ], -1000000);
			} else {
				/**
				 * Function for print cookiechoiches inline
				 */
				add_action( 'wp_footer', [ $this, 'print_script_inline'], -99999 );
			}
		}
	}

	/**
	 * Disable W3TC Page Cache.
	 */
	public function disable_w3tc_page_cache() {

		/**
		 * This fix server error 500 on php7.
		 * You do not need to difine those constant if advanced-cache doesn't exist.
		 */
		if ( ! file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) {
			return false;
		}

		// W3TC Disable Caching
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'SID' ) ){
			define('SID', true);
		}

	}


	private function in_array_match($value, $array) {
		foreach($array as $k=>$v) {
			if(preg_match('/'.str_replace(preg_quote("<---------SOMETHING--------->"), ".*", preg_quote(preg_replace( "/([\r|\n]*)/is", "", trim($v)), '/')).'/is', preg_replace( "/([\r|\n]*)/is", "", $value))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove custom script in header and footer
	 * @param  string $buffer Source of page.
	 * @return string         Return the new HTML
	 */
	public function removeCustomScript( $buffer ) {
		/**
		 * Custom script to block
		 * @var string
		 */
		$custom_script_block = $this->options['custom_script_block'] ?? '' ;

		if( ! $custom_script_block && ! $this->block_script )
			return $buffer;
		else {

			/**
			 * Replace space with <---------SEP--------->
			 * @var string
			 */
			$custom_script_block = preg_replace( "/([\r|\n]*)<---------SEP--------->([\r|\n]*)/is", "<---------SEP--------->", $custom_script_block );

			/**
			 * Convert $custom_script_block to an array
			 * @var array
			 */
			$custom_script_block_array = explode( "<---------SEP--------->", $custom_script_block );

			/**
			 * Merge the script array from new UX functionality
			 * @var array
			 */
			$custom_script_block_array = array_merge( $custom_script_block_array, $this->block_script );

			foreach( $custom_script_block_array as $single_script) {
				preg_match_all( '/' . str_replace( preg_quote( "<---------SOMETHING--------->" ), ".*", preg_quote( trim( $single_script ), '/' ) ) . '/is', $buffer, $matches );
				if ( ! empty( $matches[0] ) ) {
					foreach ( $matches[0] as $v ) {
						$buffer = str_replace( trim( $v ), "<!-- removed head from Italy Cookie Choices PHP Class -->", $buffer );
						$this->js_array[] = trim( $v );
					}
				}
			}
			return $buffer;
		}
	}

	public function bufferBodyStart() {
		if ( ob_get_contents() )
			ob_end_flush();
		ob_start();

	}

	public function bufferBodyEnd() {

		/**
		 * Check if $custom_script_block_body_exclude is set
		 * @var string
		 */
		$custom_script_block_body_exclude = $this->options['custom_script_block_body_exclude'] ?? '' ;

		/**
		 * Replace space with <---------SEP--------->
		 * @var string
		 */
		$custom_script_block_body_exclude = preg_replace( "/([\r|\n]*)<---------SEP--------->([\r|\n]*)/is", "<---------SEP--------->", $custom_script_block_body_exclude );

		/**
		 * Create array
		 * @var array
		 */
		$custom_script_block_body_exclude_array = explode( "<---------SEP--------->", $custom_script_block_body_exclude );

		if( ! is_array( $custom_script_block_body_exclude_array ) || empty( $custom_script_block_body_exclude_array[0] ) )
			$custom_script_block_body_exclude_array = [];

		/**
		 * Merge the script array from new UX functionality
		 * @var array
		 */
		$custom_script_block_body_exclude_array = array_merge( $custom_script_block_body_exclude_array, $this->allow_script );

		$buffer = ob_get_contents();
		if ( ob_get_contents() )
			ob_end_clean();
		preg_match( "/(.*)(<body.*)/s", $buffer, $matches );
		$head = $matches[1] ?? '';
		$body = $matches[2] ?? '';
		preg_match_all( $this->pattern, $body, $body_matches );

		if ( ! empty( $body_matches[0] ) ) {
			foreach ( $body_matches[0] as $k => $v ) {

				if ( ! $this->in_array_match( trim( $v ), $custom_script_block_body_exclude_array ) ) {
					$body = preg_replace( '/' . str_replace( preg_quote( "<---------SOMETHING--------->" ), ".*", preg_quote( trim( $v ), '/' ) ) . '/is', $this->valore, $body );
					$this->js_array[] = trim( $v );
				}
			}
		}

		$buffer_new = $head.$body;
		echo $buffer_new;
	}

	public function bufferFooterStart() {
		/**
		 * Check if we are in feed page, then do nothing
		 */
		if ( is_feed() )
			return;

		if ( ob_get_contents() )
			ob_end_flush();
		ob_start();
	}

	public function bufferFooterEnd() {
		/**
		 * Check if we are in feed page, then do nothing
		 */
		if ( is_feed() )
			return;

		$buffer = ob_get_contents();
		if ( ob_get_contents() )
			ob_end_clean();
		// If is an HTML request (alternative methods???).
		if( isset( $_SERVER['HTTP_ACCEPT'] ) && str_contains( $_SERVER["HTTP_ACCEPT"], 'html' ) ) {
			$buffer_new = $this->removeCustomScript( $buffer );
			/**
			 * Function for print cookiechoiches inline
			 */
			$this->print_script_inline();
			echo $buffer_new;
		} else {
			echo $buffer;
		}
	}

	public function bufferHeadStart() {
		if ( ob_get_contents() )
			ob_end_flush();
		ob_start();
	}

	public function bufferHeadEnd() {
		$buffer = ob_get_contents();
		if ( ob_get_contents() )
			ob_end_clean();
		$buffer_new = $this->removeCustomScript( $buffer );
		echo $buffer_new;
	}

	/**
	 * Script preimpostati da escludere dal blocco
	 * Preparare un array key valore a parte, unico per entrambi
	 * Questo array sarà utilizzato per generare anche le input dinamicamente
	 * array(
	 *     'facebook' => 'script'
	 * )
	 * Return the Array con gli script preimpostati.
	 */
	private function get_allowed_script() {

		$allow_iframe = $this->options['allow_iframe'] ?? [ '' ];
		$allow_script = $this->options['allow_script'] ?? [ '' ];
		$allow_embed = $this->options['allow_embed'] ?? [ '' ];

		$array = [];

		foreach ( $allow_iframe as $key => $value )
			if ( ! empty( $value ) )
				$this->allow_script[] = '<iframe<---------SOMETHING--------->' . $value . '<---------SOMETHING---------></iframe>';

		foreach ( $allow_script as $key => $value )
			if ( ! empty( $value ) )
				$this->allow_script[] = '<script<---------SOMETHING--------->' . $value . '<---------SOMETHING---------></script>';

		foreach ( $allow_embed as $key => $value )
			if ( ! empty( $value ) )
				$this->allow_script[] = '<embed<---------SOMETHING--------->' . $value . '<---------SOMETHING--------->>';

	}

	private function get_blocked_script( $val = [] ) {

		$block_iframe = $this->options['block_iframe'] ?? [ '' ];
		$block_script = $this->options['block_script'] ?? [ '' ];
		$block_embed = $this->options['block_embed'] ?? [ '' ];

		$array = [];

		foreach ( $block_iframe as $value )
			if ( ! empty( $value ) )
				$this->block_script[] = '<iframe<---------SOMETHING--------->' . $value . '<---------SOMETHING---------></iframe>';

		foreach ( $block_script as $value )
			if ( ! empty( $value ) )
				$this->block_script[] = '<script<---------SOMETHING--------->' . $value . '<---------SOMETHING---------></script>';

		foreach ( $block_embed as $value )
			if ( ! empty( $value ) )
				$this->block_script[] = '<embed<---------SOMETHING--------->' . $value . '<---------SOMETHING--------->>';

	}

	/**
	 * Function for matching embed, return the Array with embed found
	 * @param  string $pattern Pattern.
	 * @param  string $content Content.
	 */
	public function matches( $pattern, $content ) {

		preg_match_all( $this->pattern, $content, $matches );

		/**
		 * Memorizzo gli embed trovati e li appendo all'array $js_array
		 * @var array
		 */
		if ( ! empty( $matches[0] ) )
			$this->js_array = array_merge( $this->js_array, $matches[0] );

	}

	/**
	 * Erase third part embed
	 * @param string $content Article content.
	 */
	public function autoErase( $content ) {

		$this->matches( $this->pattern, $content );

		$content = preg_replace( $this->pattern, $this->valore , $content );

		return $content;
	}

	private function fnFixArray( $v ) {

		if ( is_array( $v ) or is_object( $v ) ) {

			foreach ( $v as $k1 => $v1 ) {

				$v[ $k1 ] = $this->fnFixArray( $v1 );

			}

			return $v;

		}

		if ( ! is_string( $v ) or empty( $v ) ) return $v;

		$this->matches( $this->pattern, $v );

		return preg_replace( $this->pattern, $this->valore , $v );

	}

	public function widgetErase( $instance, $widget, $args ) {

		return $this->fnFixArray( $instance );

	}

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
	public function wp_json_encode( $data, $options = 0, $depth = 512 ) {

		/**
		 * json_encode() has had extra params added over the years.
		 * $options was added in 5.3, and $depth in 5.5.
		 * We need to make sure we call it with the correct arguments.
		 */
		if ( version_compare( PHP_VERSION, '5.5', '>=' ) )
			$args = [ $data, $options, $depth ];
		elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) )
			$args = [ $data, $options ];
		else $args = [ $data ];

		$json = call_user_func_array( 'json_encode', $args );

		// If json_encode() was successful, no need to do more sanity checking.
		// ... unless we're in an old version of PHP, and json_encode() returned
		// a string containing 'null'. Then we need to do more sanity checking.
		if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || !str_contains( $json, 'null' ) ) )
			return $json;

		return call_user_func_array( 'json_encode', $args );
	}

	/**
	 * Print script inline before </body>
	 * @link https://www.cookiechoices.org/
	 */
	public function print_script_inline() {

		/**
		 * If is not active exit
		 */
		if ( ! isset( $this->options['active'] ) || is_feed() )
			return;

		$cookie_name = $this->options['cookie_name'] ?? $this->cookieName ;

		$cookie_value = $this->options['cookie_value'] ?? $this->cookieVal ;

		/**
		 * The 2.x script only restores the blocked content now,
		 * ItalyCookieChoices\Consent\UI\Banner prints the banner and reloads the page when needed.
		 * var coNA = cookie name
		 * var coVA = cookie val
		 * var jsArr = the blocked scripts to restore
		 * The other variables are read only by the 2.x banner functions.
		 * @var string
		 */
		$jsVariables = 'var coNA="'
			. $cookie_name . '",coVA="'
			. $cookie_value . '";scroll="",elPos="fixed",infoClass="",closeClass="",htmlM="",rel="",tar="",bgB="",btcB="",bPos="",bannerStyle="",contentStyle="",consText="",jsArr = '
			. $this->wp_json_encode( apply_filters( 'icc_js_array', $this->js_array ) ) . ';';

		/**
		 * Select wich file to use in debug mode
		 * @var string
		 */
		$fileJS = ( WP_DEBUG ) ? '/js/default/cookiechoices.js' : '/js/default/cookiechoices.php' ;

		$output_html = '<!-- Italy Cookie Choices --><script>' . $jsVariables . file_get_contents( ITALY_COOKIE_CHOICES_DIRNAME . $fileJS ) . '</script>';

		echo apply_filters( 'icc_output_html', $output_html );

	}
}
