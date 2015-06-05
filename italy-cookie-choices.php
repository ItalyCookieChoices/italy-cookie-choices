<?php
/**
 * Plugin Name: Italy Cookie Choices
 * Plugin URI: https://plus.google.com/u/0/communities/109254048492234113886
 * Description: Minimal code to make sure your website repect the Italian coockie law
 * Version: 2.0.0
 * Author: Enea Overclokk
 * Author URI: https://plus.google.com/u/0/communities/109254048492234113886
 * Text Domain: italy-cookie-choices
 * License: GPLv2 or later
 *
 * @package Italy Cookie Choices
 * @since 1.0.0
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * This will make shure the plugin files can't be accessed within the web browser directly.
 */
if ( !defined( 'WPINC' ) )
    die;

/**
 * Define some costant for internal use
 */
if ( !defined( 'ITALY_COOKIE_CHOICES_PLUGIN' ) )
    define('ITALY_COOKIE_CHOICES_PLUGIN', true);

/**
 * Example = F:\xampp\htdocs\italystrap\wp-content\plugins\italystrap-extended\italystrap.php
 */
if ( !defined( 'ITALY_COOKIE_CHOICES_FILE' ) )
    define('ITALY_COOKIE_CHOICES_FILE', __FILE__ );

/**
 * Example = F:\xampp\htdocs\italystrap\wp-content\plugins\italystrap-extended/
 */
if ( !defined( 'ITALY_COOKIE_CHOICES_PLUGIN_PATH' ) )
    define('ITALY_COOKIE_CHOICES_PLUGIN_PATH', plugin_dir_path( ITALY_COOKIE_CHOICES_FILE ));
/**
 * Example = italystrap-extended/italystrap.php
 */
if ( !defined( 'ITALY_COOKIE_CHOICES_BASENAME' ) )
    define('ITALY_COOKIE_CHOICES_BASENAME', plugin_basename( ITALY_COOKIE_CHOICES_FILE ));


/**
 * 
 */
if ( !class_exists( 'Italy_Cookie_Choices' ) ){

    class Italy_Cookie_Choices{

        /**
         * Definition of variables containing the configuration
         * to be applied to the various function calls wordpress
         */
        protected $capability = 'manage_options';

        /**
         * Global variables and default values
         * @var array
         */
        protected $default_options = array();

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
         * Array with embed found
         * @var array
         */
        public $js_array = array();

        /**
         * [__construct description]
         */
        public function __construct(){

            /**
             * Add Admin menù page
             */
            add_action( 'admin_menu', array( $this, 'addMenuPage') );

            /**
             * Init settings
             */
            add_action( 'admin_init', array( $this, 'italy_cl_settings_init') );

            /**
             * Add color picker in admin menù
             */
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_color_picker') );

            if ( !is_admin() ) {

                $this->options = get_option( 'italy_cookie_choices' );

                /**
                 * Shortcode to put a button in policy page
                 */
                add_shortcode( 'accept_button', array( $this, 'accept_button' ) );

                if ( !isset( $_COOKIE[ $this->options['cookie_name'] ] ) ){

                    /**
                     * Function for print cookiechoiches inline
                     */
                    add_action( 'wp_footer', array( $this, 'print_script_inline'), '9' );

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
                    // $this->valore = '<div class="el"><div style="padding:10px;margin-bottom: 18px;color: #b94a48;background-color: #f2dede;border: 1px solid #eed3d7; text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;">' . esc_attr( $this->options['text'] ) . '<button onclick="allowCookie()">Try it</button></div><!-- $0 --></div>';
                    // 
                    $this->valore = '<div class="el"><div style="padding:10px;margin-bottom: 18px;color:'.esc_attr( $banner_text_color ).';background-color:' . esc_attr( $banner_bg ) . ';text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);">' . esc_attr( $content_message_text ) . '&nbsp;&nbsp;<button onclick="allowCookie()" style="color: '.esc_attr( $banner_text_color ).';padding: 3px;font-size: 12px;line-height: 12px;text-decoration: none;text-transform: uppercase;margin:0;display: inline-block;font-weight: normal; text-align: center;  vertical-align: middle;  cursor: pointer;  border: 1px solid ' . esc_attr( $banner_text_color ) . ';background: rgba(255, 255, 255, 0.03);">' . esc_attr( $content_message_button_text ) . '</button></div><cookie></div>';

                    if ($block)
                        add_filter( 'the_content', array( $this, 'AutoErase' ), 11);

                    if ( $widget_block )
                        add_filter('widget_display_callback', array( $this, 'WidgetErase' ), 11, 3);

                    /**
                     * Only for debug
                     */
                    // var_dump($_COOKIE);
                    // var_dump(headers_list());
                    
                }
            }
        }

        /**
         * Add page for italy-cookie-choices admin page
         */
        public function addMenuPage(){

            add_options_page(
                __('Italy Cookie Choices Dashboard', 'italy-cookie-choices'),
                'Italy Cookie Choices',
                $this->capability,
                'italy-cookie-choices',
                array( $this, 'dashboard')
                );
        }

        /**
         *  The dashboard callback
         */
        public function dashboard(){

            if ( !current_user_can( $this->capability ) )
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

                ?>
                <div class="wrap">

                        <?php //settings_errors('italy_cookie_id'); ?>

                    <form action='options.php' method='post'>
                        
                        <?php
                        settings_fields( 'italy_cl_options_group' );
                        do_settings_sections( 'italy_cl_options_group' );
                        submit_button();
                        ?>
                        
                    </form>
                </div>
                <?php

        }

        /**
         * [italy_cl_settings_init description]
         * @return [type] [description]
         */
        public function italy_cl_settings_init() {

            /**
            * Load Plugin Textdomain
            */
            // load_plugin_textdomain('italy-cookie-choices', false, ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'lang/' );
            load_plugin_textdomain('italy-cookie-choices', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

            /**
             * Create default options
             * @var array
             */
            $this->default_options = array(

                'text'                          => '',
                'url'                           => '',
                'anchor_text'                   => '',
                'button_text'                   => '',
                'cookie_name'                   => $this->cookieName,
                'cookie_value'                  => $this->cookieVal,
                'content_message_text'          => '',
                'content_message_button_text'   => ''

                );

            /**
             * All options in array
             * @var array
             */
            $this->options = get_option( 'italy_cookie_choices' );

            /**
             * If the theme options don't exist, create them.
             */
            if( false === $this->options )
                add_option( 'italy_cookie_choices', $this->default_options );

            /**
             * Section options page
             */
            add_settings_section(
                'setting_section', 
                __( 'Italy Cookie Choices options page', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_settings_section_callback'), 
                'italy_cl_options_group'
            );

            /**
             * Checkbox for activation
             */
            add_settings_field( 
                'active', 
                __( 'Activate', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_active'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * How to display banner
             * Default Bar
             */
            add_settings_field( 
                'banner', 
                __( 'Where display the banner', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_banner'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * Checkbox for scroll event
             */
            add_settings_field( 
                'scroll', 
                __( 'Mouse scroll event', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_scroll'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * Checkbox for reload page
             */
            add_settings_field( 
                'reload', 
                __( 'Refresh page', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_reload'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * Input for short policy text
             */
            add_settings_field( 
                'text', 
                __( 'Text to display', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_text'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * Input for url policy page
             */
            add_settings_field( 
                'url', 
                __( 'URL for cookie policy', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_url'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * Input for anchor text
             */
            add_settings_field( 
                'anchor_text', 
                __( 'Anchor text for URL', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_anchor_text'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * Input for button text
             */
            add_settings_field( 
                'button_text', 
                __( 'Button text', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_button_text'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * Settings sections for Style
             */
            add_settings_section(
                'style_setting_section', 
                __( 'Style settings', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_style_settings_section_callback'), 
                'italy_cl_options_group'
            );

            /**
             * Checkbox for activation
             */
            add_settings_field( 
                'html_margin', 
                __( 'HTML top margin', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_html_margin'), 
                'italy_cl_options_group', 
                'style_setting_section'
            );

            /**
             * Background color for banner
             */
            add_settings_field( 
                'banner_bg', 
                __( 'Banner Background color', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_banner_bg'), 
                'italy_cl_options_group', 
                'style_setting_section'
            );

            /**
             * Color for text in banner
             */
            add_settings_field( 
                'banner_text_color', 
                __( 'Banner text color', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_banner_text_color'), 
                'italy_cl_options_group', 
                'style_setting_section'
            );

            /**
             * Settings sections for Advanced options
             */
            add_settings_section(
                'advanced_setting_section', 
                __( 'Advanced settngs', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_advanced_settings_section_callback'), 
                'italy_cl_options_group'
            );

            /**
             * cookie name
             */
            add_settings_field( 
                'cookie_name', 
                __( 'Cookie name', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_cookie_name'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );

            /**
             * cookie value
             */
            add_settings_field( 
                'cookie_value', 
                __( 'Cookie value', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_cookie_value'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );

            /**
             * Cookie policy page slug
             */
            add_settings_field( 
                'slug', 
                __( 'Cookie policy page slug', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_slug'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );

            /**
             * Checkbox for open in new page
             */
            add_settings_field( 
                'target', 
                __( 'Open policy in new page', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_target'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );

            /**
             * Checkbox for activation third part cookie eraser
             */
            add_settings_field( 
                'block', 
                __( 'Third part cookie block (beta)', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_block'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );

            /**
             * Function for content message text
             */
            add_settings_field( 
                'content_message_text', 
                __( 'Text message for locked embedded content', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_content_message_text'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );
        
            /**
             * Function for button text in message
             */
            add_settings_field( 
                'content_message_button_text', 
                __( 'Button text to activate locked embedded content', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_content_message_button_text'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );

            /**
             * Register setting
             */
            register_setting(
                'italy_cl_options_group',
                'italy_cookie_choices',
                array( $this, 'sanitize_callback')
                );


        }


        /**
         * Display message in plugin control panel
         * @return string Return message
         */
        public function italy_cl_settings_section_callback() { 

            _e( 'Customize your banner for cookie law', 'italy-cookie-choices' );

        }

        /**
         * Snippet for checkbox
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_active($args) {

            $active = ( isset( $this->options['active'] ) ) ? $this->options['active'] : '' ;
        ?>

            <input type='checkbox' name='italy_cookie_choices[active]' <?php checked( $active, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[active]">
                <?php _e( 'Display banner for Cookie Law in front-end', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Choose how to display banner in page
         * @return string       Display input and labels in plugin options page
         */
        public function italy_cl_option_banner($args) {

            $banner = ( isset( $this->options['banner'] ) ) ? $this->options['banner'] : '1' ;

        ?>

            <input name="italy_cookie_choices[banner]" type="radio" value="1" id="radio_1" <?php checked( '1', $banner ); ?> />

            <label for="radio_1">
                <?php _e( 'Top Bar (Default, Display a top bar wth your message)', 'italy-cookie-choices' ); ?>
            </label>

            <br>

            <input name="italy_cookie_choices[banner]" type="radio" value="2" id="radio_2" <?php checked( '2', $banner ); ?> />

            <label for="radio_2">
                <?php _e( 'Dialog (Display an overlay with your message)', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for checkbox
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_scroll($args) {

            $scroll = ( isset( $this->options['scroll'] ) ) ? $this->options['scroll'] : '' ;
        ?>

            <input type='checkbox' name='italy_cookie_choices[scroll]' <?php checked( $scroll, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[scroll]">
                <?php _e( 'Accepts disclosures on mouse scroll event', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for reload
         * @return strimg       Reload page after click
         */
        public function italy_cl_option_reload($args) {

            $reload = ( isset( $this->options['reload'] ) ) ? $this->options['reload'] : '' ;
        ?>

            <input type='checkbox' name='italy_cookie_choices[reload]' <?php checked( $reload, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[reload]">
                <?php _e( 'Refresh page after button click', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Textarea for the message to display
         * @return string
         */
        public function italy_cl_option_text($args) {

        ?>

            <textarea rows="5" cols="70" name="italy_cookie_choices[text]" id="italy_cookie_choices[text]" placeholder="<?php _e( 'Your short cookie policy', 'italy-cookie-choices' ) ?>" ><?php echo esc_textarea( $this->options['text'] ); ?></textarea>
            <br>
            <label for="italy_cookie_choices[text]">
                <?php echo __( 'People will see this notice only the first time that they enter your site', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Input for url policy page
         * @return string
         */
        public function italy_cl_option_url($args) {

        ?>
            <input type="text" id="italy_cookie_choices[url]" name="italy_cookie_choices[url]" value="<?php echo esc_url( $this->options['url'] ); ?>" placeholder="<?php _e( 'e.g. http://www.aboutcookies.org/', 'italy-cookie-choices' ) ?>" size="70" />
            <br>
            <label for="italy_cookie_choices[url]">
                <?php echo __( 'Insert here the link to your policy page', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Input for anchor_text
         * @return string
         */
        public function italy_cl_option_anchor_text($args) {

        ?>
            <input type="text" id="italy_cookie_choices[anchor_text]" name="italy_cookie_choices[anchor_text]" value="<?php echo esc_attr( $this->options['anchor_text'] ); ?>" placeholder="<?php _e( 'e.g. More Info', 'italy-cookie-choices' ) ?>" />

            <label for="italy_cookie_choices[anchor_text]">
                <?php echo __( 'Insert here anchor text for the link', 'italy-cookie-choices'); ?>
            </label>

        <?php

        }

        /**
         * Input for anchor_text
         * @return string
         */
        public function italy_cl_option_button_text($args) {

        ?>
            <input type="text" id="italy_cookie_choices[button_text]" name="italy_cookie_choices[button_text]" value="<?php echo esc_attr( $this->options['button_text'] ); ?>" placeholder="<?php _e( 'e.g. Close', 'italy-cookie-choices' ) ?>" />

            <label for="italy_cookie_choices[button_text]">
                <?php echo __( 'Insert here name of button (e.g. "Close") ', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * NUOVA SETTINGS SECTIONS PER LO STILE
         */

        /**
         * Display message in stile plugin panel
         * @return string
         */
        public function italy_cl_style_settings_section_callback() { 

            _e( 'Customize your style settings', 'italy-cookie-choices' );

        }

        /**
         * Snippet for checkbox
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_html_margin($args) {

            $html_margin = ( isset( $this->options['html_margin'] ) ) ? $this->options['html_margin'] : '' ;

        ?>

            <input type='checkbox' name='italy_cookie_choices[html_margin]' <?php checked( $html_margin, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[html_margin]">
                <?php _e( 'Add a page top margin for info top bar', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for checkbox
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_banner_bg($args) {

            $banner_bg = ( isset( $this->options['banner_bg'] ) ) ? $this->options['banner_bg'] : '' ;

        ?>

            <input type="text" id="italy_cookie_choices[banner_bg]" name="italy_cookie_choices[banner_bg]" value="<?php echo esc_attr( $banner_bg ); ?>" placeholder="<?php echo esc_attr( $banner_bg ); ?>" class="color-field" data-default-color="#fff"/>


            <label for="italy_cookie_choices[banner_bg]">
                <?php _e( 'Custom Background color for banner', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for banner text color
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_banner_text_color($args) {

            $banner_text_color = ( isset( $this->options['banner_text_color'] ) ) ? $this->options['banner_text_color'] : '' ;

        ?>

            <input type="text" id="italy_cookie_choices[banner_text_color]" name="italy_cookie_choices[banner_text_color]" value="<?php echo esc_attr( $banner_text_color ); ?>" placeholder="<?php echo esc_attr( $banner_text_color ); ?>" class="color-field" data-default-color="#000"/>

            <label for="italy_cookie_choices[banner_text_color]">
                <?php _e( 'Custom text color for banner', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * NUOVA SETTINGS SECTIONS PER LE OPZIONI AVANZATE
         */

        /**
         * Display message in plugin advanced setting section
         * @return string
         */
        public function italy_cl_advanced_settings_section_callback() { 

            _e( 'Customize your advanced settings', 'italy-cookie-choices' );

        }

        /**
         * Snippet for cookie name
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_cookie_name($args) {

            $cookie_name = ( isset( $this->options['cookie_name'] ) ) ? $this->options['cookie_name'] : $this->cookieName ;

        ?>
            <input type="text" id="italy_cookie_choices[cookie_name]" name="italy_cookie_choices[cookie_name]" value="<?php echo esc_attr( $cookie_name ); ?>" placeholder="<?php echo esc_attr( $this->cookieName ); ?>" />

            <label for="italy_cookie_choices[cookie_name]">
                <?php _e( 'Insert your cookie name (Default: displayCookieConsent)', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for cookie value
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_cookie_value($args) {

            $cookie_value = ( isset( $this->options['cookie_value'] ) ) ? $this->options['cookie_value'] : $this->cookieVal ;

        ?>
            <input type="text" id="italy_cookie_choices[cookie_value]" name="italy_cookie_choices[cookie_value]" value="<?php echo esc_attr( $cookie_value ); ?>" placeholder="<?php echo esc_attr( $this->cookieVal ); ?>" />

            <label for="italy_cookie_choices[cookie_value]">
                <?php _e( 'Insert your cookie value (Default: y)', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Slug for cookie policy page
         * @return strimg       Slug for cookie policy page Default null
         */
        public function italy_cl_option_slug($args) {

            $slug = ( isset( $this->options['slug'] ) ) ? $this->options['slug'] : '' ;

        ?>
            <input type="text" id="italy_cookie_choices[slug]" name="italy_cookie_choices[slug]" value="<?php echo esc_attr( $slug ); ?>" placeholder="<?php _e( 'e.g. your-policy-url.html', 'italy-cookie-choices' ); ?>" />

            <label for="italy_cookie_choices[slug]">
                <?php _e( 'Insert your cookie policy page slug (e.g. your-policy-url), it will display only topbar in your cookie policy page', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for target checkbox
         * @return strimg       Activate for open policy page in new tab 
         *                      Default open in same tab
         */
        public function italy_cl_option_target($args) {

            $target = ( isset( $this->options['target'] ) ) ? $this->options['target'] : '' ;

        ?>

            <input type='checkbox' name='italy_cookie_choices[target]' <?php checked( $target, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[target]">
                <?php _e( 'Open your cookie policy page in new one', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for target checkbox
         * @return strimg       Activate for open policy page in new tab 
         *                      Default open in same tab
         */
        public function italy_cl_option_block($args) {

            $block = ( isset( $this->options['block'] ) ) ? $this->options['block'] : '' ;
            $widget_block = ( isset( $this->options['widget_block'] ) ) ? $this->options['widget_block'] : '' ;

        ?>

            <input type='checkbox' name='italy_cookie_choices[block]' <?php checked( $block, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[block]">
                <?php _e( 'Cookie from any embed in your content (Beta)', 'italy-cookie-choices' ); ?>
            </label>
            <br>
            <input type='checkbox' name='italy_cookie_choices[widget_block]' <?php checked( $widget_block, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[widget_block]">
                <?php _e( 'Cookie from any embed in your widget area (Beta)', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Textarea for content_message_text
         * @return string
         */
        public function italy_cl_option_content_message_text($args) {

        ?>
            <textarea rows="5" cols="70" name="italy_cookie_choices[content_message_text]" id="italy_cookie_choices[content_message_text]" placeholder="<?php _e( 'Your lock message for embedded contents inside posts, pages and widgets', 'italy-cookie-choices' ) ?>" ><?php echo esc_textarea( $this->options['content_message_text'] ); ?></textarea>
            <br>
            <label for="italy_cookie_choices[content_message_text]">
                <?php echo __( 'People will see this notice only the first time that they enter your site', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Input for content_message_button_text
         * @return string
         */
        public function italy_cl_option_content_message_button_text($args) {

        ?>
            <input type="text" id="italy_cookie_choices[content_message_button_text]" name="italy_cookie_choices[content_message_button_text]" value="<?php echo esc_attr( $this->options['content_message_button_text'] ); ?>" placeholder="<?php _e( 'e.g. Close', 'italy-cookie-choices' ) ?>" />

            <label for="italy_cookie_choices[content_message_button_text]">
                <?php echo __( 'Insert here name of button (e.g. "Close") ', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Sanitize data
         * @param  array $input Data to sanitize
         * @return array        Data sanitized
         */
        public function sanitize_callback( $input ){

            $new_input = array();

            if( isset( $input['active'] ) )
                $new_input['active'] =  $input['active'];

            if( isset( $input['banner'] ) )
                $new_input['banner'] =  $input['banner'];

            if( isset( $input['scroll'] ) )
                $new_input['scroll'] =  $input['scroll'];

            if( isset( $input['reload'] ) )
                $new_input['reload'] =  $input['reload'];

            if( isset( $input['text'] ) )
                $new_input['text'] = sanitize_text_field( $input['text'] );

            if( isset( $input['url'] ) )
                $new_input['url'] = sanitize_text_field( $input['url'] );

            if( isset( $input['anchor_text'] ) )
                $new_input['anchor_text'] = sanitize_text_field( $input['anchor_text'] );

            if( isset( $input['button_text'] ) )
                $new_input['button_text'] = sanitize_text_field( $input['button_text'] );

            /**
             * Sezione per lo stile
             */
            if( isset( $input['html_margin'] ) )
                $new_input['html_margin'] =  $input['html_margin'];

            if( empty( $input['banner_bg'] ) )
                $new_input['banner_bg'] =  '#fff';
            elseif ( isset( $input['banner_bg'] ) )
                $new_input['banner_bg'] =  sanitize_text_field( $input['banner_bg'] );

            if( empty( $input['banner_text_color'] ) )
                $new_input['banner_text_color'] =  '#000';
            elseif ( isset( $input['banner_text_color'] ) )
                $new_input['banner_text_color'] =  sanitize_text_field( $input['banner_text_color'] );

            /**
             * Sezione per le opzioni avanzate
             * Esempio per add_settings_error()
             * @link https://wordpress.org/support/topic/how-to-use-add_settings_error-for-nested-options-array?replies=2
             * @link http://pastebin.com/K4kJ0DNG
             */
            if( empty( $input['cookie_name'] ) ){
                add_settings_error( 'italy_cookie_id', 'cookie_name_ID', __('Cookie name field it can\'t be empty. Restored default name.', 'italy-cookie-choices' ), 'error');
                $new_input['cookie_name'] = $this->cookieName;
            }
            else
                $new_input['cookie_name'] =  sanitize_text_field( $input['cookie_name'] );

            if( empty( $input['cookie_value'] ) ){
                add_settings_error( 'italy_cookie_id', 'cookie_name_ID', __('Cookie value field it can\'t be empty. Restored default value.', 'italy-cookie-choices' ), 'error');
                $new_input['cookie_value'] =  $this->cookieVal;
            }
            else
                $new_input['cookie_value'] = sanitize_text_field( $input['cookie_value'] );

            if( isset( $input['slug'] ) )
                $new_input['slug'] = sanitize_text_field( $input['slug'] );

            if( isset( $input['target'] ) )
                $new_input['target'] =  $input['target'];

            if( isset( $input['block'] ) )
                $new_input['block'] =  $input['block'];

            if( isset( $input['widget_block'] ) )
                $new_input['widget_block'] =  $input['widget_block'];

            if( isset( $input['content_message_text'] ) )
                $new_input['content_message_text'] =  $input['content_message_text'];
        
            if( isset( $input['content_message_button_text'] ) )
                $new_input['content_message_button_text'] =  $input['content_message_button_text'];

            return $new_input;

        }

        /**
         * Function for color picker in admin
         * @param  string $hook_suffix Hook for script
         * @return               Append script
         * @link https://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/
         * @link http://code.tutsplus.com/articles/how-to-use-wordpress-color-picker-api--wp-33067
         */
        public function enqueue_color_picker( $hook_suffix ) {

                // first check that $hook_suffix is appropriate for your admin page
                wp_enqueue_style( 'wp-color-picker' );

                // wp_enqueue_script( 'jquery' );

                wp_enqueue_script(
                    'italy-cookie-choices-script',
                    plugins_url('admin/js/src/script.js', ITALY_COOKIE_CHOICES_FILE ),
                    array(
                        // 'jquery',
                        'wp-color-picker'
                        ),
                    null,
                    true
                    );

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

        /**
         * Erase third part in widget area
         * @param [type] $instance [description]
         * @param [type] $widget   [description]
         * @param [type] $args     [description]
         */
        public function WidgetErase($instance, $widget, $args){
            
            $fnFixArray = function($v) use (&$fnFixArray){
                if(is_array($v) or is_object($v)){
                    foreach($v as $k1=>&$v1){
                        $v1 = $fnFixArray($v1);
                    }
                    return $v;
                }

                if(!is_string($v) or empty($v)) return $v;

                $this->matches( $this->pattern, $v );

                return preg_replace( $this->pattern, $this->valore , $v);

            };

            return $fnFixArray($instance);

        }

        /**
         * Print script inline
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
            if ( $this->options['banner'] === '1' || !empty( $this->options['slug'] ) && ( is_page( $this->options['slug'] ) || is_single( $this->options['slug'] ) ) )
                $banner = 'Bar';
            elseif ( $this->options['banner'] === '2' )
                $banner = 'Dialog';
            else
                $banner = '';

            /**
             * Accept on scroll
             * @var bol
             */
            $scroll = ( isset( $this->options['scroll'] ) ) ? $this->options['scroll'] : '' ;

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
            $banner = 'document.addEventListener("DOMContentLoaded", function(event) {cookieChoices.showCookieConsent' . $banner . '(' . wp_json_encode( $this->options['text'] ) . ', "' . esc_js( $this->options['button_text'] ) . '", "' . esc_js( $this->options['anchor_text'] ) . '", "' . esc_url( $this->options['url'] ) . '");});';

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
             * Se l'optione è selezionata aggiunge un margine per non nascondere il contenuto dalla top bar
             * @var string
             */
            $style = '<style>.icc{margin-top:36px}</style>';
            
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
            $jsVariables = 'var coNA="' . $cookie_name . '",coVA="' . $cookie_value . '";scroll="' . $scroll . '",elPos="fixed",infoClass="",closeClass="",htmlM="' . $htmlM . '",rel="' . $reload . '",tar="' . $target . '",bgB="' . $banner_bg . '",btcB="' . $banner_text_color . '",jsArr = ' . wp_json_encode( $this->js_array ) . ';';

            /**
             * Noscript snippet in case browser has JavaScript disabled
             * @var string
             */
            $noscript = '<noscript><style>html{margin-top:35px}</style><div id="cookieChoiceInfo" style="position:absolute;width:100%;margin:0px;left:0px;top:0px;padding:4px;z-index:9999;text-align:center;background-color:rgb(238, 238, 238);"><span>' . wp_json_encode( $this->options['text'] ) . '</span><a href="' . esc_url( $this->options['url'] ) . '" target="_blank" style="margin-left:8px;">' . esc_js( $this->options['anchor_text'] ) . '</a><a id="cookieChoiceDismiss" href="#" style="margin-left:24px;display:none;">' . esc_js( $this->options['button_text'] ) . '</a></div></div></noscript>';

            echo '<!-- Italy Cookie Choices -->' . $style . '<script>' . $jsVariables;
            require 'js/cookiechoices.php';
            echo $banner . '</script>' . $noscript;

        }

        /**
         * Shortcode per stampare il bottone nella pagina della policy
         * @param  array $atts    Array con gli attributi dello shortcode
         * @param  string $content content of shortcode
         * @return string          Button per l'accettazione
         */
        public function accept_button( $atts, $content = null ) {

            $button_text = ( isset( $this->options['button_text'] ) ) ? $this->options['button_text'] : '' ;

            return '<span class="el"><button onclick="allowCookie()">' . esc_attr( $button_text ) . '</button></span>';

        }

    }// class
}//endif

new Italy_Cookie_Choices;