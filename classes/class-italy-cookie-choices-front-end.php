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
             * Asseggno il valore allo slug nel costruttore
             * @var bolean
             */
            $this->slug = ( isset( $this->options['slug'] ) ) ? esc_attr( $this->options['slug'] ) : '' ;

            /*
             * Set cookie if the user agree navigating through the pages of the site
             */
            $secondView = false;

            if(
                // if is an HTML request (alternative methods???)
                (strpos($_SERVER["HTTP_ACCEPT"],'html') !== false) &&
                //if the page isn't privacy page
                ($_SERVER['REQUEST_URI']!=$this->slug) && 
                //if HTTP_REFERER is set
                (isset($_SERVER['HTTP_REFERER'])) && 
                //if isn't refresh
                (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)!=$_SERVER['REQUEST_URI']) &&
                //if referrer is not privacy page (to be evaluated)
                (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)!=$this->slug) && 
                //if the cookie is not already set
                (!isset( $_COOKIE[ $this->options['cookie_name'] ] )) && 
                //if the referer is in the same domain
                (parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)==$_SERVER['HTTP_HOST']) &&
                // If the secondView options is checked
                ( $secondViewOpt )
            ) {
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
                $banner_bg = ( isset( $this->options['banner_bg'] ) ) ? $this->options['banner_bg'] : '' ;

                /**
                 * Color for text
                 * @var string
                 */
                $banner_text_color = ( isset( $this->options['banner_text_color'] ) ) ? $this->options['banner_text_color'] : '' ;

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
                 * Checkbox custom scripts block
                 * @var bol
                 */
                $custom_script_block = ( isset( $this->options['custom_script_block'] ) ) ? $this->options['custom_script_block'] : '' ;

                /**
                 * Text to put inside locked post and widget contents
                 * including the button text
                 * @var string
                 */
                $content_message_text = ( isset( $this->options['content_message_text'] ) ) ? $this->options['content_message_text']    : '' ;

                /**
                 * Text for button in locked content and widget
                 * @var string
                 */
                $content_message_button_text = ( isset( $this->options['content_message_button_text'] ) ) ? $this->options['content_message_button_text'] : '' ;

                /**
                 * Replacement for regex
                 * @var string
                 */
                // $this->valore = '<div class="el"><div style="padding:10px;margin-bottom: 18px;color: #b94a48;background-color: #f2dede;border: 1px solid #eed3d7; text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;">' . esc_attr( $this->options['text'] ) . '<button onclick="cookieChoices.removeCookieConsent()">Try it</button></div><!-- $0 --></div>';
                // 
                $this->valore = '<div class="el"><div style="padding:10px;margin-bottom: 18px;color:'.esc_attr( $banner_text_color ).';background-color:' . esc_attr( $banner_bg ) . ';text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);">' . esc_attr( $content_message_text ) . '&nbsp;&nbsp;<button onclick="cookieChoices.removeCookieConsent()" style="color: '.esc_attr( $banner_text_color ).';padding: 3px;font-size: 12px;line-height: 12px;text-decoration: none;text-transform: uppercase;margin:0;display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;  cursor: pointer;  border: 1px solid ' . esc_attr( $banner_text_color ) . ';background: rgba(255, 255, 255, 0.03);">' . esc_attr( $content_message_button_text ) . '</button></div><cookie></div>';

                if ($block)
                    add_filter( 'the_content', array( $this, 'AutoErase' ), 11);

                if ( $widget_block )
                    add_filter( 'widget_display_callback', array( $this, 'WidgetErase' ), 11, 3 );

                if ( $all_block ) {
                    //add_action('wp_footer', array( $this, 'catchBody' ), -1000000);
                    add_action('wp_head', array( $this, 'bufferBodyStart' ), 1000000);
                    add_action('wp_footer', array( $this, 'bufferBodyEnd' ), -1000000);
                }
                if( $custom_script_block !== '' ) {
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


        public function removeCustomScript($buffer) {
            $custom_script_block = ( isset( $this->options['custom_script_block'] ) ) ? $this->options['custom_script_block'] : '' ;
            if($custom_script_block=='') {
                return $buffer;
            } else {
                $custom_script_block = preg_replace( "/([\r|\n]*)<---------SEP--------->([\r|\n]*)/is", "<---------SEP--------->", $custom_script_block );
                $custom_script_block_array = explode("<---------SEP--------->", $custom_script_block);
                foreach($custom_script_block_array AS $single_script) {
                    $count_replace = 0;
                    $buffer = str_replace(trim($single_script), "<!-- removed from Italy Cookie Choices Plugin -->", $buffer, $count_replace);
                    if($count_replace>0)
                        $this->js_array[] = trim($single_script);
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
            $buffer = ob_get_contents();
            if (ob_get_contents()) 
                ob_end_clean();
            preg_match("/(.*)(<body.*)/s", $buffer, $matches);
            $head = $matches[1];
            $body = $matches[2];
            $this->matches( $this->pattern, $body );
            $body = preg_replace( $this->pattern, $this->valore , $body);
            $buffer_new = $head.$body;
            echo '<!-- ICCStartBody -->'.$buffer_new.'<!-- ICCEndBody -->';
        }

        public function bufferFooterStart() {
            if (ob_get_contents()) 
                ob_end_flush();
            ob_start();
        }

        public function bufferFooterEnd() {
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
            if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
                $args = array( $data, $options, $depth );
            } elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
                $args = array( $data, $options );
            } else {
                $args = array( $data );
            }
         
            $json = call_user_func_array( 'json_encode', $args );
         
            // If json_encode() was successful, no need to do more sanity checking.
            // ... unless we're in an old version of PHP, and json_encode() returned
            // a string containing 'null'. Then we need to do more sanity checking.
            if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) )  {
                return $json;
            }
         
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
            if ( !isset( $this->options['active'] ) )
                return;

            /**
             * Select what kind of banner to display
             */
            // if ( $this->options['banner'] === '1' || !empty( $this->options['slug'] ) && ( is_page( $this->options['slug'] ) || is_single( $this->options['slug'] ) ) )
            if ( $this->options['banner'] === '1' ) {

                $banner = 'Bar'; 
                $bPos = 'top:0';

            } elseif ( $this->options['banner'] === '2' && !( is_page( $this->slug ) ||  is_single( $this->slug ) ) ) {

                $banner = 'Dialog';
                $bPos = 'top:0';

            } elseif ( $this->options['banner'] === '3' ) {

                $banner = 'Bar'; 
                $bPos = 'bottom:0';

            } else {

                $banner = 'Bar';
                $bPos = 'top:0';

            }

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
             * Snippet for display banner
             * @uses json_encode Funzione usate per il testo del messaggio.
             *                   Ricordarsi che aggiunge già
             *                   le doppie virgolette "" alla stringa
             * @var string
             */
            $banner = 'document.addEventListener("DOMContentLoaded", function(event) {cookieChoices.showCookieConsent' . $banner . '(' . $this->wp_json_encode( $this->options['text'] ) . ', "' . esc_js( $this->options['button_text'] ) . '", "' . esc_js( $this->options['anchor_text'] ) . '", "' . esc_url( $this->options['url'] ) . '");});';

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
             * Js_Template vlue
             * @var string
             */
            $js_template = ( isset( $this->options['js_template'] ) ) ? $this->options['js_template'] : $this->js_template ;

            /**
             * If is set html_margin checkbox in admin panel then add margin-top to HTML tag
             * @var bol
             */
            $htmlM = ( isset( $this->options['html_margin'] ) ) ? $this->options['html_margin'] : '' ;

            /**
             * If set open policy page in new browser tab
             * @var bol
             */
            $target = ( isset( $this->options['target'] ) ) ? $this->options['target'] : '' ;

            /**
             * Colore dello sfondo della dialog/topbar
             * @var string
             */
            $banner_bg = ( isset( $this->options['banner_bg'] ) ) ? esc_attr( $this->options['banner_bg'] ) : '' ;

            /**
             * Colore del font della dialog/topbar
             * @var string
             */
            $banner_text_color = ( isset( $this->options['banner_text_color'] ) ) ? esc_attr( $this->options['banner_text_color'] ) : '' ;

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
             * @var string
             */
            $jsVariables = 'var coNA="' . $cookie_name . '",coVA="' . $cookie_value . '";scroll="' . $scroll . '",elPos="fixed",infoClass="",closeClass="",htmlM="' . $htmlM . '",rel="' . $reload . '",tar="' . $target . '",bgB="' . $banner_bg . '",btcB="' . $banner_text_color . '",bPos="' . $bPos . '",jsArr = ' . $this->wp_json_encode( $this->js_array ) . ';';

            /**
             * Noscript snippet in case browser has JavaScript disabled
             * @var string
             */
            $noscript = '<noscript><style>html{margin-top:35px}</style><div id="cookieChoiceInfo" style="position:absolute;width:100%;margin:0px;left:0px;top:0px;padding:4px;z-index:9999;text-align:center;background-color:rgb(238, 238, 238);"><span>' . $this->wp_json_encode( $this->options['text'] ) . '</span><a href="' . esc_url( $this->options['url'] ) . '" target="_blank" style="margin-left:8px;">' . esc_js( $this->options['anchor_text'] ) . '</a><a id="cookieChoiceDismiss" href="#" style="margin-left:24px;display:none;">' . esc_js( $this->options['button_text'] ) . '</a></div></div></noscript>';

            /**
             * Select wich file to use in debug mode
             * @var string
             */
            $fileJS = ( WP_DEBUG ) ? '/js/'.$js_template.'/cookiechoices.js' : '/js/'.$js_template.'/cookiechoices.php' ;

            $output_html = '<!-- Italy Cookie Choices -->' . '<script>' . $jsVariables . file_get_contents( ITALY_COOKIE_CHOICES_DIRNAME . $fileJS ) .  $banner . '</script>' . $noscript;

            echo $output_html;

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

    }// class
}//endif