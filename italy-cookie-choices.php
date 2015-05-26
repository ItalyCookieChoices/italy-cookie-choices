<?php
/**
 * Plugin Name: Italy Cookie Choices
 * Plugin URI: https://plus.google.com/u/0/communities/109254048492234113886
 * Description: Minimal code to make sure your website repect the Italian coockie law
 * Version: 1.1.3
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
         * [__construct description]
         */
        public function __construct(){

            /**
             * Add Admin menù page
             */
            add_action( 'admin_menu', array( $this, 'addMenuPage') );

            /**
             * 
             */
            add_action( 'admin_init', array( $this, 'italy_cl_settings_init') );

            /**
             * 
             */
            add_action( 'wp_footer', array( $this, 'print_script_inline'), '9' );

            /**
             * Only for debug
             */
            // var_dump($_COOKIE);
            // var_dump(headers_list());
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

                        <?php settings_errors(); ?>

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

                'text'          => '',
                'url'           => '',
                'anchor_text'   => '',
                'button_text'   => ''

                );

            /**
             * [$this->options description]
             * @var [type]
             */
            $this->options = get_option( 'italy_cookie_choices' );

            /**
             * If the theme options don't exist, create them.
             */
            if( false === $this->options )
                add_option( 'italy_cookie_choices', $this->default_options );

            /**
             * 
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
             * 
             */
            add_settings_field( 
                'text', 
                __( 'Text to display', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_text'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * 
             */
            add_settings_field( 
                'url', 
                __( 'URL for cookie policy', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_url'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * 
             */
            add_settings_field( 
                'anchor_text', 
                __( 'Anchor text for URL', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_anchor_text'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * 
             */
            add_settings_field( 
                'button_text', 
                __( 'Button text', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_button_text'), 
                'italy_cl_options_group', 
                'setting_section'
                );

            /**
             * 
             */
            register_setting(
                'italy_cl_options_group',
                'italy_cookie_choices',
                array( $this, 'sanitize_callback')
                );


        }


        /**
         * [italy_cl_settings_section_callback description]
         * @return [type] [description]
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

            if( isset( $input['text'] ) )
                $new_input['text'] = sanitize_text_field( $input['text'] );

            if( isset( $input['url'] ) )
                $new_input['url'] = sanitize_text_field( $input['url'] );

            if( isset( $input['anchor_text'] ) )
                $new_input['anchor_text'] = sanitize_text_field( $input['anchor_text'] );

            if( isset( $input['button_text'] ) )
                $new_input['button_text'] = sanitize_text_field( $input['button_text'] );

            return $new_input;

        }

        /**
         * Print script inline
         * @return string Print script inline
         * @link https://www.cookiechoices.org/
         */
        public function print_script_inline(){

            $this->options = get_option( 'italy_cookie_choices' );

            /**
             * If is not active exit
             */
            if ( !isset( $this->options['active'] ) )
                return;

            if ( $this->options['banner'] === '1' )
                $banner = 'Bar';
            elseif ( $this->options['banner'] === '2' )
                $banner = 'Dialog';
            else
                $banner = '';

            /**
             * Snippet for display banner
             * @uses json_encode Funzione usate per il testo del messaggio.
             *                   Ricordarsi che aggiunge già
             *                   le doppie virgolette "" alla stringa
             * @var string
             */
            $banner = 'document.addEventListener("DOMContentLoaded", function(event) {cookieChoices.showCookieConsent' . $banner . '(' . wp_json_encode( $this->options['text'] ) . ', "' . esc_js( $this->options['button_text'] ) . '", "' . esc_js( $this->options['anchor_text'] ) . '", "' . esc_url( $this->options['url'] ) . '");});';

            $cookieName = 'displayCookieConsent';
            $cookieVal = 'y';

            /**
             * Noscript snippet in case browser has JavaScript disabled
             * @var string
             */
            $noscript = '<noscript><style>html{margin-top:35px}</style><div id="cookieChoiceInfo" style="position:absolute;width:100%;margin:0px;left:0px;top:0px;padding:4px;z-index:9999;text-align:center;background-color:rgb(238, 238, 238);"><span>' . wp_json_encode( $this->options['text'] ) . '</span><a href="' . esc_url( $this->options['url'] ) . '" target="_blank" style="margin-left:8px;">' . esc_js( $this->options['anchor_text'] ) . '</a><a id="cookieChoiceDismiss" href="#" style="margin-left:24px;display:none;">' . esc_js( $this->options['button_text'] ) . '</a></div></div></noscript>';

            echo '<!-- Italy Cookie Choices --><script>';
            require 'js/cookiechoices.php';
            echo $banner . '</script>' . $noscript;

        }

    }// class
}//endif

new Italy_Cookie_Choices;