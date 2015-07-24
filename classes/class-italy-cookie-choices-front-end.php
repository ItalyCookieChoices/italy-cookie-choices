<?php
/**
 * Class for Italy Cookie Choices Admin
 */
if ( !class_exists( 'Italy_Cookie_Choices_Front_End' ) ){

    class Italy_Cookie_Choices_Front_End{

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
         * [__construct description]
         */
        public function __construct(){

            $this->options = get_option( 'italy_cookie_choices' );

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

                setcookie($this->options['cookie_name'], $this->options['cookie_value'], time()+(3600*24*365), '/');
                $secondView = true;
            }

            /**
             * Shortcode to put a button in policy page
             */
            add_shortcode( 'accept_button', array( $this, 'accept_button' ) );

            if ( !isset( $_COOKIE[ $this->options['cookie_name'] ] ) && !$secondView ){

                // W3TC Disable Caching
                if ( !defined( 'DONOTCACHEPAGE' ) )
                    define('DONOTCACHEPAGE', true);
                if ( !defined( 'SID' ) )
                    define('SID', true);

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
                $this->valore = '<div class="el"><div style="padding:10px;margin-bottom: 18px;color:' . esc_attr( $banner_text_color ) . ';background-color:' . esc_attr( $banner_bg ) . ';text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);">' . $content_message_text . '&nbsp;&nbsp;<button onclick="cookieChoices.removeCookieConsent()" style="color: ' . esc_attr( $banner_text_color ) . ';padding: 3px;font-size: 12px;line-height: 12px;text-decoration: none;text-transform: uppercase;margin:0;display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;  cursor: pointer;  border: 1px solid ' . esc_attr( $banner_text_color ) . ';background: rgba(255, 255, 255, 0.03);">' . $content_message_button_text . '</button></div><cookie></div>';

                if ($block)
                    add_filter( 'the_content', array( $this, 'AutoErase' ), 11);

                if ( $widget_block )
                    add_filter( 'widget_display_callback', array( $this, 'WidgetErase' ), 11, 3 );

                if ( $all_block ) {
                    //add_action('wp_footer', array( $this, 'catchBody' ), -1000000);
                    add_action('wp_head', array( $this, 'bufferBodyStart' ), 1000000);
                    add_action('wp_footer', array( $this, 'bufferBodyEnd' ), -1000000);
                }
                if( $custom_script_block !== '' && $all_block ) {
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

        }//__construct


        /**
         * Get the current page url
         * @link http://www.brosulo.net/content/informatica/ottenere-la-url-completa-da-una-pagina-php-0
         */
        private function CurrentPageURL() {

            if ( isset( $_SERVER['HTTPS'] ) )
                $pageURL = $_SERVER['HTTPS'];
            else
                $pageURL = NULL;

            $pageURL = $pageURL === 'on' ? 'https://' : 'http://';
            $pageURL .= $_SERVER["SERVER_NAME"];
            $pageURL .= ( $_SERVER['SERVER_PORT'] !== '80' ) ? ':' . $_SERVER["SERVER_PORT"] : '';
            $pageURL .= $_SERVER["REQUEST_URI"];

            return $pageURL;

        }

        /**
         * Check if is the policy page
         * Required url input
         * @param  boolean $secondViewOpt Check for second view option
         * @return boolean                Return bolean value
         */
        private function is_policy_page( $secondViewOpt = false ){

            if(
                // if is an HTML request (alternative methods???)
                ( strpos( $_SERVER["HTTP_ACCEPT"],'html' ) !== false ) &&
                //if the page isn't privacy page
                // ( $_SERVER['REQUEST_URI'] != $this->slug ) && 
                ( $this->CurrentPageURL() !== $this->url ) && 
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


        private function in_array_match($value, $array) {
            foreach($array as $k=>$v) {
                if(preg_match('/'.str_replace(preg_quote("<---------SOMETHING--------->"), ".*", preg_quote(preg_replace( "/([\r|\n]*)/is", "", trim($v)), '/')).'/is', preg_replace( "/([\r|\n]*)/is", "", $value))) {
                    return true;
                }
            }
            return false;
        }

        public function removeCustomScript($buffer) {
            $custom_script_block = ( isset( $this->options['custom_script_block'] ) ) ? $this->options['custom_script_block'] : '' ;
            if($custom_script_block=='') {
                return $buffer;
            } else {
                $custom_script_block = preg_replace( "/([\r|\n]*)<---------SEP--------->([\r|\n]*)/is", "<---------SEP--------->", $custom_script_block );
                $custom_script_block_array = explode("<---------SEP--------->", $custom_script_block);
                foreach($custom_script_block_array AS $single_script) {
                    preg_match_all('/'.str_replace(preg_quote("<---------SOMETHING--------->"), ".*", preg_quote(trim($single_script), '/')).'/is', $buffer, $matches);
                    if(!empty($matches[0])) {
                        foreach($matches[0] AS $v) {
                            $buffer = str_replace(trim($v), "<!-- removed head from Italy Cookie Choices PHP Class -->", $buffer);
                            $this->js_array[] = trim($v);
                        }
                    }
                }
                return $buffer;
            }
        }

        public function bufferBodyStart() {
            if (ob_get_contents()) 
                ob_end_flush();
            ob_start();

        }

        public function bufferBodyEnd() {
            $custom_script_block_body_exclude = ( isset( $this->options['custom_script_block_body_exclude'] ) ) ? $this->options['custom_script_block_body_exclude'] : '' ;
            $custom_script_block_body_exclude = preg_replace( "/([\r|\n]*)<---------SEP--------->([\r|\n]*)/is", "<---------SEP--------->", $custom_script_block_body_exclude );
            $custom_script_block_body_exclude_array = explode("<---------SEP--------->", $custom_script_block_body_exclude);

            if(!is_array($custom_script_block_body_exclude_array) || empty($custom_script_block_body_exclude_array[0]))
                $custom_script_block_body_exclude_array = array();

            $buffer = ob_get_contents();
            if (ob_get_contents()) 
                ob_end_clean();
            preg_match("/(.*)(<body.*)/s", $buffer, $matches);
            $head = $matches[1];
            $body = $matches[2];
            preg_match_all( $this->pattern, $body, $body_matches);
            if ( !empty( $body_matches[0] ) ) {
                foreach($body_matches[0] AS $k => $v) {
                    if(!$this->in_array_match(trim($v), $custom_script_block_body_exclude_array)) {
                        $body = preg_replace('/'.str_replace(preg_quote("<---------SOMETHING--------->"), ".*", preg_quote(trim($v), '/')).'/is', $this->valore, $body);
                        $this->js_array[] = trim($v);
                    }
                }
            }
            $buffer_new = $head.$body;
            echo '<!-- ICCStartBody -->'.$buffer_new.'<!-- ICCEndBody -->';
        }

        public function bufferFooterStart() {
            /**
             * Check if we are in feed page, then do nothing
             */
            if ( is_feed() )
                return;

            if (ob_get_contents()) 
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
            if (ob_get_contents()) 
                ob_end_clean();
            // if is an HTML request (alternative methods???)
            if(strpos($_SERVER["HTTP_ACCEPT"],'html') !== false) {
                $buffer_new = $this->removeCustomScript($buffer);
                /**
                 * Function for print cookiechoiches inline
                 */
                $this->print_script_inline();
                echo '<!-- ICCStartFooter -->'.$buffer_new.'<!-- ICCEndFooter -->';
            } else {
                echo $buffer;
            }
        }

        public function bufferHeadStart() {
            if (ob_get_contents()) 
                ob_end_flush();
            ob_start();
        }

        public function bufferHeadEnd() {
            $buffer = ob_get_contents();
            if (ob_get_contents()) 
                ob_end_clean();
            $buffer_new = $this->removeCustomScript($buffer);
            echo '<!-- ICCStartHead -->'.$buffer_new.'<!-- ICCEndHead -->';
        }

        /**
         * Function for matching embed
         * @param  string $pattern Pattern
         * @param  string $content Content
         * @return array          Array with embed found
         */
        public function matches( $pattern, $content ){

            preg_match_all( $this->pattern, $content, $matches );

            /**
             * Memorizzo gli embed trovati e li appendo all'array $js_array
             * @var [type]
             */
            if ( !empty( $matches[0] ) )
                $this->js_array = array_merge( $this->js_array, $matches[0] );

        }

        /**
         * Erase third part embed
         * @param string $content Article content
         */
        public function AutoErase( $content ) {

            $this->matches( $this->pattern, $content );

            $content = preg_replace( $this->pattern, $this->valore , $content);

            
            return $content;
        }

        private function fnFixArray($v) {
            if(is_array($v) or is_object($v)){
                foreach($v as $k1=>$v1){
                    $v[$k1] = $this->fnFixArray($v1);
                }
                return $v;
            }

            if(!is_string($v) or empty($v)) return $v;

            $this->matches( $this->pattern, $v );

            return preg_replace( $this->pattern, $this->valore , $v);
        }

        /**
         * Erase third part in widget area
         * @param [type] $instance [description]
         * @param [type] $widget   [description]
         * @param [type] $args     [description]
         */
        public function WidgetErase($instance, $widget, $args){
            return $this->fnFixArray($instance);
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

            /*
             * json_encode() has had extra params added over the years.
             * $options was added in 5.3, and $depth in 5.5.
             * We need to make sure we call it with the correct arguments.
             */
            if ( version_compare( PHP_VERSION, '5.5', '>=' ) )
                $args = array( $data, $options, $depth );
            elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) )
                $args = array( $data, $options );
            else
                $args = array( $data );
         
            $json = call_user_func_array( 'json_encode', $args );
         
            // If json_encode() was successful, no need to do more sanity checking.
            // ... unless we're in an old version of PHP, and json_encode() returned
            // a string containing 'null'. Then we need to do more sanity checking.
            if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) )
                return $json;
         
            return call_user_func_array( 'json_encode', $args );
        }

        /**
         * Print script inline before </body>
         * @return string Print script inline
         * @link https://www.cookiechoices.org/
         */
        public function print_script_inline(){

            // $this->options = get_option( 'italy_cookie_choices' );

            /**
             * If is not active exit
             */
            if ( !isset( $this->options['active'] ) || is_feed() )
                return;

            /**
             * Accept on scroll
             * @var bol
             */
            $scroll = ( isset( $this->options['scroll'] ) && !( is_page( $this->slug ) ||  is_single( $this->slug ) ) ) ? $this->options['scroll'] : '' ;

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
            $banner_bg = ( isset( $this->options['banner_bg'] ) && !empty( $this->options['banner_bg'] ) ) ? esc_attr( $this->options['banner_bg'] ) : '#fff' ;

            /**
             * Colore del font della dialog/topbar
             * @var string
             */
            $banner_text_color = ( isset( $this->options['banner_text_color'] ) && !empty( $this->options['banner_text_color'] ) ) ? esc_attr( $this->options['banner_text_color'] ) : '#000' ;

            /**
             * Custom CSS
             * @var string
             */
            $customCSS = ( isset( $this->options['customCSS'] ) ) ? esc_attr( $this->options['customCSS'] ) : '' ;

            /**
             * CSS class for div bannerStyle
             * @var string
             */
            $bannerStyle = ( isset( $this->options['bannerStyle'] ) && !empty( $this->options['bannerStyle'] ) ) ? esc_attr( $this->options['bannerStyle'] ) : 'bannerStyle' ;

            /**
             * CSS class for div content
             * @var string
             */
            $contStyle = ( isset( $this->options['contentStyle'] ) && !empty( $this->options['contentStyle'] ) ) ? esc_attr( $this->options['contentStyle'] ) : 'contentStyle' ;

            /**
             * CSS class for text in span
             * @var string
             */
            $consentText = ( isset( $this->options['consentText'] ) && !empty( $this->options['consentText'] ) ) ? esc_attr( $this->options['consentText'] ) : 'consentText' ;

            /**
             * CSS class for info link
             * @var string
             */
            $infoClass = ( isset( $this->options['infoClass'] ) && !empty( $this->options['infoClass'] ) ) ? esc_attr( $this->options['infoClass'] ) : 'italybtn' ;

            /**
             * CSS class for close link
             * @var string
             */
            $closeClass = ( isset( $this->options['closeClass'] ) && !empty( $this->options['closeClass'] ) ) ? esc_attr( $this->options['closeClass'] ) : 'italybtn' ;

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
            if ( $js_template === 'bigbutton' ){

                $buttonStyle = '.' . $buttonClass . '{color:' . $banner_text_color . ';padding:7px 12px;font-size:18px;line-height:18px;text-decoration:none;text-transform:uppercase;margin:10px 20px 2px 0;letter-spacing: 0.125em;display:inline-block;font-weight:normal;text-align:center;  vertical-align:middle;cursor:pointer;border:1px solid ' . $banner_text_color . ';background:rgba(255, 255, 255, 0.03);}.' . $consentText . '{display:block}';

                $text_align = 'left';

            }elseif ( $js_template === 'smallbutton' ){

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

                $contentStyle = ( $js_template === 'bigbutton' || $js_template === 'smallbutton' ) ? '.contentStyle{max-width:980px;margin-right:auto;margin-left:auto;padding:15px;}' : '' ;

                $style = ( $js_template === 'custom' ) ? $customCSS : '#cookieChoiceInfo{background-color: ' . $banner_bg . ';color: ' . $banner_text_color . ';left:0;margin:0;padding:4px;position:fixed;text-align:' . $text_align . ';top:0;width:100%;z-index:9999;}' . $contentStyle . $buttonStyle;

                $bPos = 'top:0'; // Deprecato

                $htmlM = ( isset( $this->options['html_margin'] ) ) ? $this->options['html_margin'] : '' ;

            } elseif ( $this->options['banner'] === '2' && !( is_page( $this->slug ) ||  is_single( $this->slug ) ) ) {

                $banner = 'Dialog';

                $style = ( $js_template === 'custom' ) ? $customCSS : '.glassStyle{position:fixed;width:100%;height:100%;z-index:999;top:0;left:0;opacity:0.5;filter:alpha(opacity=50);background-color:#ccc;}.' . $bannerStyle . '{min-width:100%;z-index:9999;position:fixed;top:25%;}.contentStyle{position:relative;background-color:' . $banner_bg . ';padding:20px;box-shadow:4px 4px 25px #888;max-width:80%;margin:0 auto;}' . $buttonStyle;

                $bPos = 'top:0'; // Deprecato

            } elseif ( $this->options['banner'] === '3' ) {

                $banner = 'Bar';

                $contentStyle = ( $js_template === 'bigbutton' || $js_template === 'smallbutton' ) ? '.contentStyle{max-width:980px;margin-right:auto;margin-left:auto;padding:15px;}' : '' ;

                $style = ( $js_template === 'custom' ) ? $customCSS : '#cookieChoiceInfo{background-color: ' . $banner_bg . ';color: ' . $banner_text_color . ';left:0;margin:0;padding:4px;position:fixed;text-align:' . $text_align . ';bottom:0;width:100%;z-index:9999;}' . $contentStyle . $buttonStyle;

                $bPos = 'bottom:0'; // Deprecato

            } else {

                $banner = 'Bar';

                $style = ( $js_template === 'custom' ) ? $customCSS : '#cookieChoiceInfo{background-color: ' . $banner_bg . ';color: ' . $banner_text_color . ';left:0;margin:0;padding:4px;position:fixed;text-align:center;top:0;width:100%;z-index:9999;}' . $contentStyle . $buttonStyle;

                $bPos = 'top:0'; // Deprecato

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
            $jsVariables = 'var coNA="' . $cookie_name . '",coVA="' . $cookie_value . '";scroll="' . $scroll . '",elPos="fixed",infoClass="' . $infoClass . '",closeClass="' . $closeClass . '",htmlM="' . $htmlM . '",rel="' . $reload . '",tar="' . $target . '",bgB="' . $banner_bg . '",btcB="' . $banner_text_color . '",bPos="' . $bPos . '",bannerStyle="' . $bannerStyle . '",contentStyle="' . $contStyle . '",consText="' . $consentText . '",jsArr = ' . $this->wp_json_encode( apply_filters( 'icc_js_array', $this->js_array ) ) . ';';

            /**
             * Snippet per il multilingua
             * function get_string return multilanguage $value
             * if isn't installed any language plugin return $value
             */
            $text = $this->wp_json_encode( wp_kses_post( get_string( 'Italy Cookie Choices', 'Banner text', $this->options['text'] ) ) );

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

            echo apply_filters( 'icc_output_html', $output_html );

        }

        /**
         * Shortcode per stampare il bottone nella pagina della policy
         * @param  array $atts    Array con gli attributi dello shortcode
         * @param  string $content content of shortcode
         * @return string          Button per l'accettazione
         */
        public function accept_button( $atts, $content = null ) {

            $button_text = ( isset( $this->options['button_text'] ) ) ? $this->options['button_text'] : '' ;

            return '<span class="el"><button onclick="cookieChoices.removeCookieConsent()">' . esc_attr( $button_text ) . '</button></span>';

        }

        /**
         * Display cookie, only for internal use
         * @return string
         */
        private function _display_cookie(){

            $cookie_list = '<ul>';

            foreach ( $_COOKIE as $key => $val )
                $cookie_list .= '<li>Cooke name: ' . $key . ' - val: ' . $val . '</li>';

            $cookie_list .= '</ul>';

            return $cookie_list;

        }

    }// class
}//endif