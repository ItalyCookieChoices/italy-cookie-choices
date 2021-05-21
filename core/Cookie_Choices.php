<?php
/**
 * Class for Italy Cookie Choices Admin
 */

namespace Italy_Cookie_Choices\Core;

use \Overclokk\Cookie\Cookie_Interface;

class Cookie_Choices {

	/**
	 * Option
	 * @var array
	 */
	private $options = array();

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
	 * Inizialize banner template to default
	 * @var string
	 */
	private $js_template = 'default';

	/**
	 * Array with embed found
	 * @var array
	 */
	public $js_array = array();

	/**
	 * If exist set a page slug
	 * @var string
	 */
	private $slug = '';

	/**
	 * URL for policy page
	 * @var string
	 */
	private $url = '';

	/**
	 * Array with list of allowed script|iframe|embed
	 * @var array
	 */
	private $allow_script = array();

	/**
	 * Array with list of blocked script|iframe|embed
	 * @var array
	 */
	private $block_script = array();

	/**
	 * Cookie_Interface
	 *
	 * @var Cookie_Interface
	 */
	private $cookie = null;

	/**
	 * Cookie_Choices constructor.
	 * @param array $options
	 * @param Cookie_Interface $cookie
	 */
	public function __construct( array $options, Cookie_Interface $cookie ){
		$this->options = $options;
		$this->cookie = $cookie;
	}

	/**
	 * Execute the Class
	 */
	public function run() {

		/**
		 * Check for second view option
		 * @var bol
		 */
		$secondViewOpt = ( isset( $this->options['secondView'] ) ) ? $this->options['secondView'] : '' ;

		/**
		 * Assegno il valore allo slug nel costruttore
		 * Default ID 1 perché su null non settava correttamente lo scroll se il valore era assente
		 * @var bolean
		 */
		$this->slug = ( isset( $this->options['slug'] ) && !empty( $this->options['slug'] ) ) ? esc_attr( get_string( 'Italy Cookie Choices', 'Banner slug', $this->options['slug'] ) ) : 1 ;

		/**
		 * Assegno il valore della URL della policy page
		 * Default ID 1 perché su null non settava correttamente lo scroll se il valore era assente
		 * @var bolean
		 */
		$this->url = ( isset( $this->options['url'] ) && !empty( $this->options['url'] ) ) ? esc_url( get_string( 'Italy Cookie Choices', 'Banner url', $this->options['url'] ) ) : 1 ;

		/*
		 * Set cookie if the user agree navigating through the pages of the site
		 */
		$secondView = false;

		if( $this->is_policy_page( $secondViewOpt ) ) {

			$this->cookie->set(
				esc_attr( $this->options['cookie_name'] ),
				esc_attr( $this->options['cookie_value'] ),
				3600 * 24 * 365,
				'/'
			);

			// setcookie(
			// 	esc_attr( $this->options['cookie_name'] ),
			// 	esc_attr( $this->options['cookie_value'] ),
			// 	time() + ( 3600 * 24 * 365 ),
			// 	'/'
			// );

			$secondView = true;
		}

		/**
		 * Shortcode to put a button in policy page
		 */
		add_shortcode( 'accept_button', array( $this, 'accept_button' ) );
		add_shortcode( 'delete_cookie', array( $this, '_delete_cookie' ) );

		if ( ! isset( $_COOKIE[ $this->options['cookie_name'] ] ) && ! $secondView ){

			$this->disable_w3tc_page_cache();

			/**
			 * Background color for banner
			 * @var string
			 */
			$banner_bg = ( isset( $this->options['banner_bg'] ) ) ? $this->options['banner_bg'] : '#ffffff' ;

			/**
			 * Color for text
			 * @var string
			 */
			$banner_text_color = ( isset( $this->options['banner_text_color'] ) ) ? $this->options['banner_text_color'] : '#000000' ;

			/**
			 * Text for banner
			 * @var string
			 */
			$text = ( isset( $this->options['text'] ) ) ? $this->options['text']    : '' ;

			/**
			 * Text for buttom
			 * @var [type]
			 */
			$button_text = ( isset( $this->options['button_text'] ) ) ? $this->options['button_text'] : '' ;

			/**
			 * Checkbox for third part cookie in content
			 * @var bol
			 */
			$block = ( isset( $this->options['block'] ) ) ? $this->options['block'] : '' ;

			/**
			 * Checkbox for third part cookie in widget
			 * @var bol
			 */
			$widget_block = ( isset( $this->options['widget_block'] ) ) ? $this->options['widget_block'] : '' ;

			/**
			 * Checkbox for third part cookie in all page (except head and footer)
			 * @var bol
			 */
			$all_block = ( isset( $this->options['all_block'] ) ) ? $this->options['all_block'] : '' ;

			/**
			 * Checkbox custom scripts block in BODY
			 * @var string
			 */
			$custom_script_block_body_exclude = ( isset( $this->options['custom_script_block_body_exclude'] ) ) ? $this->options['custom_script_block_body_exclude'] : '' ;

			$this->get_blocked_script();
			$this->get_allowed_script();

			/**
			 * Checkbox custom scripts block in HEAD and FOOTER
			 * @var string
			 */
			$custom_script_block = ( isset( $this->options['custom_script_block'] ) ) ? $this->options['custom_script_block'] : '' ;

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
				add_filter( 'the_content', array( $this, 'AutoErase' ), 11 );
			}

			if ( $widget_block ) {
				add_filter( 'widget_display_callback', array( $this, 'WidgetErase' ), 11, 3 );
			}

			if ( $all_block ) {
				add_action('wp_head', array( $this, 'bufferBodyStart' ), 1000000);
				add_action('wp_footer', array( $this, 'bufferBodyEnd' ), -1000000);
			}
			if( ( $custom_script_block || $this->block_script ) && $all_block ) {
				add_action('template_redirect', array( $this, 'bufferHeadStart' ), 2);
				add_action('wp_head', array( $this, 'bufferHeadEnd' ), 99999);

				add_action('wp_footer', array( $this, 'bufferFooterStart' ), -99998);
				add_action('shutdown', array( $this, 'bufferFooterEnd' ), -1000000);
			} else {
				/**
				 * Function for print cookiechoiches inline
				 */
				add_action( 'wp_footer', array( $this, 'print_script_inline'), -99999 );
			}

			/**
			 * Only for debug
			 */
			// var_dump($_COOKIE);
			// var_dump(headers_list());

		}
	}

	/**
	 * Disable W3TC Page Cache.
	 */
	public function disable_w3tc_page_cache() {

		/**
		 * This fix server error 500 on php7.
		 * You do not need to define those constants if advanced-cache doesn't exist.
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

	/**
	 * Get the current page url
	 * @link http://www.brosulo.net/content/informatica/ottenere-la-url-completa-da-una-pagina-php-0
	 *
	 * @return string The current page url
	 */
	private function get_current_page_url() {

		$page_url = null;

		if ( isset( $_SERVER['HTTPS'] ) ) {
			$page_url = $_SERVER['HTTPS'];
		}

		$page_url = 'on' === $page_url ? 'https://' : 'http://';
		$page_url .= $_SERVER["SERVER_NAME"];
		$page_url .= '80' !== $_SERVER['SERVER_PORT'] ? ':' . $_SERVER["SERVER_PORT"] : '';
		$page_url .= $_SERVER["REQUEST_URI"];

		return esc_url( $page_url );

	}

	/**
	 * Check if is the policy page
	 * Required url input
	 * @param  boolean $secondViewOpt Check for second view option
	 * @return boolean                Return bolean value
	 */
	private function is_policy_page( $secondViewOpt = false ){

		if (
			// if HTTP_ACCEPT is set
			( isset( $_SERVER['HTTP_ACCEPT'] ) &&

			// if is an HTML request (alternative methods???)
			strpos( $_SERVER['HTTP_ACCEPT'], 'html' ) !== false ) &&

			//if the page isn't privacy page
			// ( $_SERVER['REQUEST_URI'] != $this->slug ) &&
			( $this->get_current_page_url() !== $this->url ) &&
			//if HTTP_REFERER is set
			( isset( $_SERVER['HTTP_REFERER'] ) ) &&
			//if isn't refresh
			( parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) !== $_SERVER['REQUEST_URI'] ) &&
			//if referrer is not privacy page (to be evaluated)
			// ( parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH ) != $this->slug ) &&
			// ( $_SERVER['HTTP_REFERER'] !== $this->url ) &&
			//if the cookie is not already set
			( !isset( $_COOKIE[ $this->options['cookie_name'] ] ) ) &&
			//if the referer is in the same domain
			( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ) === $_SERVER['HTTP_HOST'] )  &&
			// If the secondView options is checked
			( $secondViewOpt )
		)
			return true;
		else
			return false;

	}

	private function in_array_match( $value, $array ) {

		foreach( $array as $k => $v ) {
			if( preg_match( '/' . str_replace( preg_quote("<---------SOMETHING--------->"), ".*", preg_quote( preg_replace( "/([\r|\n]*)/is", "", trim( $v ) ), '/' ) ) . '/is', preg_replace( "/([\r|\n]*)/is", "", $value ) ) ) {
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
		$custom_script_block = ( isset( $this->options['custom_script_block'] ) ) ? $this->options['custom_script_block'] : '' ;

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

	/**
	 * Start head buffer
	 *
	 * @hooked 'template_redirect' - 2
	 */
	public function bufferHeadStart() {

		if ( ob_get_contents() ) {
			ob_end_flush();
		}

		ob_start();
	}

	/**
	 * End head buffer
	 *
	 * @hooked 'wp_head' - 99999
	 */
	public function bufferHeadEnd() {

		$buffer = ob_get_contents();

		if ( ob_get_contents() ) {
			ob_end_clean();
		}

		$buffer_new = $this->removeCustomScript( $buffer );

		printf(
			is_debug() ? '<!-- ICCStartHead -->%s<!-- ICCEndHead -->' : '%s',
			$buffer_new
		);
	}

	/**
	 * Start body buffer
	 *
	 * @hooked 'wp_head' - 1000000
	 */
	public function bufferBodyStart() {

		if ( ob_get_contents() ) {
			ob_end_flush();
		}

		ob_start();
	}

	/**
	 * End body buffer
	 *
	 * @hooked 'wp_footer' - -1000000
	 */
	public function bufferBodyEnd() {

		/**
		 * Check if $custom_script_block_body_exclude is set
		 * @var string
		 */
		$custom_script_block_body_exclude = ( isset( $this->options['custom_script_block_body_exclude'] ) ) ? $this->options['custom_script_block_body_exclude'] : '' ;

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
			$custom_script_block_body_exclude_array = array();

		/**
		 * Merge the script array from new UX functionality
		 * @var array
		 */
		$custom_script_block_body_exclude_array = array_merge( $custom_script_block_body_exclude_array, $this->allow_script );

		$buffer = ob_get_contents();

		if ( ob_get_contents() ) {
			ob_end_clean();
		}

		preg_match( "/(.*)(<body.*)/s", $buffer, $matches );

		$head = isset( $matches[1] ) ? $matches[1] : '';
		$body = isset( $matches[2] ) ? $matches[2] : '';

		preg_match_all( $this->pattern, $body, $body_matches );

		if ( ! empty( $body_matches[0] ) ) {
			foreach ( $body_matches[0] as $k => $v ) {

				if ( ! $this->in_array_match( trim( $v ), $custom_script_block_body_exclude_array ) ) {
					$body = preg_replace( '/' . str_replace( preg_quote( "<---------SOMETHING--------->" ), ".*", preg_quote( trim( $v ), '/' ) ) . '/is', $this->valore, $body );
					$this->js_array[] = trim( $v );
				}
			}
		}

		$buffer_new = $head . $body;

		printf(
			is_debug() ? '<!-- ICCStartBody -->%s<!-- ICCEndBody -->' : '%s',
			$buffer_new
		);
	}

	/**
	 * Start footer buffer
	 *
	 * @hooked 'wp_footer' - -99998
	 */
	public function bufferFooterStart() {
		/**
		 * Check if we are in feed page, then do nothing
		 */
		if ( is_feed() ) {
			return;
		}

		if ( ob_get_contents() ) {
			ob_end_flush();
		}

		ob_start();
	}

	/**
	 * Start footer buffer
	 *
	 * @hooked 'shutdown' - -1000000
	 */
	public function bufferFooterEnd() {
		/**
		 * Check if we are in feed page, then do nothing
		 */
		if ( is_feed() ) {
			return;
		}

		$buffer = ob_get_contents();

		if ( ob_get_contents() ) {
			ob_end_clean();
		}

		// If is an HTML request (alternative methods???).
		if( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER["HTTP_ACCEPT"], 'html' ) !== false ) {
			$buffer_new = $this->removeCustomScript( $buffer );

			/**
			 * Function for printing cookiechoiches inline
			 */
			$this->print_script_inline();
			printf(
				is_debug() ? '<!-- ICCStartFooter -->%s<!-- ICCEndFooter -->' : '%s',
				$buffer_new
			);

		} else {
			echo $buffer;
		}
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

		$allow_iframe = ( isset( $this->options['allow_iframe'] ) ) ? $this->options['allow_iframe'] : array( '' );
		$allow_script = ( isset( $this->options['allow_script'] ) ) ? $this->options['allow_script'] : array( '' );
		$allow_embed = ( isset( $this->options['allow_embed'] ) ) ? $this->options['allow_embed'] : array( '' );

		$array = array();

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

	private function get_blocked_script( $val = array() ) {

		$block_iframe = ( isset( $this->options['block_iframe'] ) ) ? $this->options['block_iframe'] : array( '' );
		$block_script = ( isset( $this->options['block_script'] ) ) ? $this->options['block_script'] : array( '' );
		$block_embed = ( isset( $this->options['block_embed'] ) ) ? $this->options['block_embed'] : array( '' );

		$array = array();

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
	 *
	 * @param  string $pattern Pattern.
	 * @param  string $content Content.
	 */
	public function matches( $pattern, $content ) {

		preg_match_all( $pattern, $content, $matches );

		/**
		 * Memorizzo gli embed trovati e li appendo all'array $js_array
		 *
		 * @var array
		 */
		if ( ! empty( $matches[0] ) ) {
			$this->js_array = array_merge( $this->js_array, $matches[0] );
		}

	}

	/**
	 * Erase third part embed
	 *
	 * @param string $content Article content.
	 */
	public function AutoErase( $content ) {

		$this->matches( $this->pattern, $content );

		$content = preg_replace( $this->pattern, $this->valore, $content );

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

	public function WidgetErase( $instance, $widget, $args ) {

		return $this->fnFixArray( $instance );
	}

	/**
	 * Print script inline before </body>
	 * @return string Print script inline
	 * @link https://www.cookiechoices.org/
	 */
	private function build_script_inline() {

		/**
		 * If is not active exit
		 */
		if ( ! isset( $this->options['active'] ) || is_feed() )
			return;

		/**
		 * Accept on scroll
		 * @var bol
		 */
		$scroll = ( isset( $this->options['scroll'] ) && ! ( is_page( $this->slug ) ||  is_single( $this->slug ) ) ) ? $this->options['scroll'] : '' ;

		/**
		 * Reload on accept
		 * @var bol
		 */
		$reload = ( isset( $this->options['reload'] ) ) ? $this->options['reload'] : '' ;

		/**
		 * ADVANCED OPTIONS
		 */
		/**
		 * Cookie name
		 * @var string
		 */
		$cookie_name = ( isset( $this->options['cookie_name'] ) ) ? $this->options['cookie_name'] : $this->cookieName ;

		/**
		 * Cookie value
		 * @var string/bolean
		 */
		$cookie_value = ( isset( $this->options['cookie_value'] ) ) ? $this->options['cookie_value'] : $this->cookieVal ;

		/**
		 * Js_Template value
		 * @var string
		 */
		// $js_template = ( isset( $this->options['js_template'] ) && $this->options['js_template'] !== 'custom') ? $this->options['js_template'] : $this->js_template ;
		$js_template = ( isset( $this->options['js_template'] ) ) ? $this->options['js_template'] : $this->js_template ;

		/**
		 * If is set html_margin checkbox in admin panel then add margin-top to HTML tag
		 * @var bol
		 */
		$htmlM = '' ;

		/**
		 * If set open policy page in new browser tab
		 * @var bol
		 */
		$target = ( isset( $this->options['target'] ) ) ? $this->options['target'] : '' ;

		/**
		 * Colore dello sfondo della dialog/topbar
		 * @var string
		 */
		$banner_bg = ( isset( $this->options['banner_bg'] ) && ! empty( $this->options['banner_bg'] ) ) ? esc_attr( $this->options['banner_bg'] ) : '#fff' ;

		/**
		 * Colore del font della dialog/topbar
		 * @var string
		 */
		$banner_text_color = ( isset( $this->options['banner_text_color'] ) && ! empty( $this->options['banner_text_color'] ) ) ? esc_attr( $this->options['banner_text_color'] ) : '#000' ;

		/**
		 * Custom CSS
		 * @var string
		 */
		$customCSS = ( isset( $this->options['customCSS'] ) ) ? esc_attr( $this->options['customCSS'] ) : '' ;

		/**
		 * CSS class for div bannerStyle
		 * @var string
		 */
		$bannerStyle = ( isset( $this->options['bannerStyle'] ) && ! empty( $this->options['bannerStyle'] ) ) ? esc_attr( $this->options['bannerStyle'] ) : 'bannerStyle' ;

		/**
		 * CSS class for div content
		 * @var string
		 */
		$contStyle = ( isset( $this->options['contentStyle'] ) && ! empty( $this->options['contentStyle'] ) ) ? esc_attr( $this->options['contentStyle'] ) : 'contentStyle' ;

		/**
		 * CSS class for text in span
		 * @var string
		 */
		$consentText = ( isset( $this->options['consentText'] ) && ! empty( $this->options['consentText'] ) ) ? esc_attr( $this->options['consentText'] ) : 'consentText' ;

		/**
		 * CSS class for info link
		 * @var string
		 */
		$infoClass = ( isset( $this->options['infoClass'] ) && ! empty( $this->options['infoClass'] ) ) ? esc_attr( $this->options['infoClass'] ) : 'italybtn' ;

		/**
		 * CSS class for close link
		 * @var string
		 */
		$closeClass = ( isset( $this->options['closeClass'] ) && ! empty( $this->options['closeClass'] ) ) ? esc_attr( $this->options['closeClass'] ) : 'italybtn' ;

		/**
		 * If $infoClass and $closeClass are exactly alike print only first var
		 * @var [type]
		 */
		$buttonClass = ( $infoClass === $closeClass ) ? $infoClass : $infoClass . ',.' . $closeClass;

		/**
		 * Attribute for text for template
		 * @var string
		 */
		$text_align = 'center';

		/**
		 * CSS for content style
		 * @var string
		 */
		$contentStyle = '';

		/**
		 * Button style
		 * @var string
		 */
		$buttonStyle = '.' . $buttonClass . '{margin-left:10px;}';

		/**
		 * Conditional button style for bigbutton or smallbutton
		 * Default margin
		 */
		if ( 'bigbutton' === $js_template ) {

			$buttonStyle = '.' . $buttonClass . '{color:' . $banner_text_color . ';padding:7px 12px;font-size:18px;line-height:18px;text-decoration:none;text-transform:uppercase;margin:10px 20px 2px 0;letter-spacing: 0.125em;display:inline-block;font-weight:normal;text-align:center;  vertical-align:middle;cursor:pointer;border:1px solid ' . $banner_text_color . ';background:rgba(255, 255, 255, 0.03);}.' . $consentText . '{display:block}';

			$text_align = 'left';

		} elseif ( 'smallbutton' === $js_template ) {

			$buttonStyle = '.' . $buttonClass . '{color:' . $banner_text_color . ';padding:3px 7px;font-size:14px;line-height:14px;text-decoration:none;text-transform:uppercase;margin:10px 20px 2px 0;letter-spacing: 0.115em;display:inline-block;font-weight:normal;text-align:center;  vertical-align:middle;cursor:pointer;border:1px solid ' . $banner_text_color . ';background:rgba(255, 255, 255, 0.03);}.' . $consentText . '{display:block}';

			$text_align = 'left';

		}

		/**
		 * Select what kind of banner to display
		 * @var $banner Bar/Dialog
		 * @var $contentStyle Style for content div
		 * @var $style Style for banner
		 * @var $bPos Deprecated
		 * @var $htmlM Bolean for margin top
		 */
		if ( $this->options['banner'] === '1' ) {

			$banner = 'Bar';

			$contentStyle = ( 'bigbutton' === $js_template || 'smallbutton' === $js_template ) ? '.contentStyle{max-width:980px;margin-right:auto;margin-left:auto;padding:15px;}' : '' ;

			$style = ( 'custom' === $js_template ) ? $customCSS : '#cookieChoiceInfo{background-color: ' . $banner_bg . ';color: ' . $banner_text_color . ';left:0;margin:0;padding:4px;position:fixed;text-align:' . $text_align . ';top:0;width:100%;z-index:9999;}' . $contentStyle . $buttonStyle;

			$bPos = 'top:0'; // Deprecato.

			$htmlM = ( isset( $this->options['html_margin'] ) ) ? $this->options['html_margin'] : '' ;

		} elseif ( $this->options['banner'] === '2' && ! ( is_page( $this->slug ) ||  is_single( $this->slug ) ) ) {

			$banner = 'Dialog';

			$style = ( 'custom' === $js_template ) ? $customCSS : '.glassStyle{position:fixed;width:100%;height:100%;z-index:999;top:0;left:0;opacity:0.5;filter:alpha(opacity=50);background-color:#ccc;}.' . $bannerStyle . '{min-width:100%;z-index:9999;position:fixed;top:25%;}.contentStyle{position:relative;background-color:' . $banner_bg . ';padding:20px;box-shadow:4px 4px 25px #888;max-width:80%;margin:0 auto;}' . $buttonStyle;

			$bPos = 'top:0'; // Deprecato.

		} elseif ( $this->options['banner'] === '3' ) {

			$banner = 'Bar';

			$contentStyle = ( 'bigbutton' === $js_template || 'smallbutton' === $js_template ) ? '.contentStyle{max-width:980px;margin-right:auto;margin-left:auto;padding:15px;}' : '' ;

			$style = ( 'custom' === $js_template ) ? $customCSS : '#cookieChoiceInfo{background-color: ' . $banner_bg . ';color: ' . $banner_text_color . ';left:0;margin:0;padding:4px;position:fixed;text-align:' . $text_align . ';bottom:0;width:100%;z-index:9999;}' . $contentStyle . $buttonStyle;

			$bPos = 'bottom:0'; // Deprecato.

		} else {

			$banner = 'Bar';

			$style = ( 'custom' === $js_template ) ? $customCSS : '#cookieChoiceInfo{background-color: ' . $banner_bg . ';color: ' . $banner_text_color . ';left:0;margin:0;padding:4px;position:fixed;text-align:center;top:0;width:100%;z-index:9999;}' . $contentStyle . $buttonStyle;

			$bPos = 'top:0'; // Deprecato.

		}

		/**
		 * Declarations of JS variables and set parameters
		 * var elPos = Gestisce la Posizione banner nella funzione _createHeaderElement
		 * var infoClass = aggiunge una classe personalizzata per il link info
		 * var closeClass = aggiunge una classe personalizzata per il link di accettazione
		 * var htmlM = Aggiunge un margine a HTML per la top bar
		 * var coNA = cookie name
		 * var coVA = cookie val
		 * var rel = Setto il reload per la pagina all'accettazione
		 * var tar = Target -blank
		 * var bgB = Colore del background della topbar/dialog
		 * var btcB = Colore del font della topbar/dialog
		 * var bannerStyle = Variabile per le classe del contenitore
		 * var contentStyle = Variabile per le classe del contenitore del contenuto
		 * var consText = Variabile per le classe dello span per il testo
		 * @var string
		 */

		$jsVariables = 'var coNA="' . $cookie_name . '",coVA="' . $cookie_value . '";scroll="' . $scroll . '",elPos="fixed",infoClass="' . $infoClass . '",closeClass="' . $closeClass . '",htmlM="' . $htmlM . '",rel="' . $reload . '",tar="' . $target . '",bgB="' . $banner_bg . '",btcB="' . $banner_text_color . '",bPos="' . $bPos . '",bannerStyle="' . $bannerStyle . '",contentStyle="' . $contStyle . '",consText="' . $consentText . '",jsArr = ' . wp_json_encode( apply_filters( 'icc_js_array', $this->js_array ) ) . ';';

		/**
		 * Snippet per il multilingua
		 * function get_string return multilanguage $value
		 * if isn't installed any language plugin return $value
		 */
		$text = wp_kses_post( get_string( 'Italy Cookie Choices', 'Banner text', $this->options['text'] ) );

		$url = esc_url( get_string( 'Italy Cookie Choices', 'Banner url', $this->options['url'] ) );

		$anchor_text = esc_js( get_string( 'Italy Cookie Choices', 'Banner anchor text', $this->options['anchor_text'] ) );

		$button_text = esc_js( get_string( 'Italy Cookie Choices', 'Banner button text', $this->options['button_text'] ) );

		/**
		 * Snippet for display banner
		 * @uses json_encode Funzione usate per il testo del messaggio.
		 *                   Ricordarsi che aggiunge già
		 *                   le doppie virgolette "" alla stringa
		 * @var string
		 */
		$banner = 'document.addEventListener("DOMContentLoaded", function(event) {cookieChoices.showCookieConsent' . $banner . '(' . $text . ', "' . $button_text . '", "' . $anchor_text . '", "' . $url . '");});';

		/**
		 * Noscript snippet in case browser has JavaScript disabled
		 * @var string
		 */
		$noscript = '<noscript><style type="text/css">html{margin-top:35px}</style><div id="cookieChoiceInfo"><span>' . $text . '</span><a href="' . $url . '" class="' . $infoClass . '" target="_blank">' . $anchor_text . '</a></div></noscript>';

		/**
		 * Select wich file to use in debug mode
		 * @var string
		 */
		// $fileJS = ( WP_DEBUG ) ? '/js/' . $js_template . '/cookiechoices.js' : '/js/' . $js_template . '/cookiechoices.php' ;
		$fileJS = ( WP_DEBUG ) ? '/js/default/cookiechoices.js' : '/js/default/cookiechoices.php' ;

		$output_html = '<!-- Italy Cookie Choices -->' . '<style type="text/css">' . $style . '</style><script>' . $jsVariables . file_get_contents( ITALY_COOKIE_CHOICES_DIRNAME . $fileJS ) .  $banner . '</script>' . $noscript;

		return apply_filters( 'icc_output_html', $output_html );
	}

	public function print_script_inline() {
		echo $this->build_script_inline();
	}

	/**
	 * Shortcode per stampare il bottone nella pagina della policy
	 * @param  array  $atts    Array con gli attributi dello shortcode.
	 * @param  string $content Content of shortcode.
	 * @return string          Button per l'accettazione
	 */
	public function accept_button( $atts, $content = null ) {

		$button_text = ( isset( $this->options['button_text'] ) ) ? $this->options['button_text'] : '' ;

		return '<span class="el"><button onclick="cookieChoices.removeCookieConsent()">' . esc_attr( $button_text ) . '</button></span>';

	}

	/**
	 * Shortcode per stampare il bottone nella pagina della policy
	 * @param  array  $atts    Array con gli attributi dello shortcode.
	 * @param  string $content Content of shortcode.
	 * @return string          Button per l'accettazione
	 */
	public function _delete_cookie( $atts, $content = null ) {

		// $button_text = ( isset( $this->options['button_text'] ) ) ? $this->options['button_text'] : '' ;
		$button_text = 'Delete cookie';

		return '<span class="ele"><button onclick="deleteCookie()">' . esc_attr( $button_text ) . '</button></span>';

	}

	/**
	 * Display cookie, only for internal use
	 * @return string
	 */
	private function _display_cookie() {

		$cookie_list = '<ul>';

		foreach ( $_COOKIE as $key => $val )
			$cookie_list .= '<li>Cooke name: ' . esc_attr( $key ) . ' - val: ' . esc_attr( $val ) . '</li>';

		$cookie_list .= '</ul>';

		return $cookie_list;

	}
} // class
