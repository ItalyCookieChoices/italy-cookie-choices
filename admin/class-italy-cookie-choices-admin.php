<?php
/**
 * Class for Italy Cookie Choices Admin
 */
if ( !class_exists( 'Italy_Cookie_Choices_Admin' ) ){

    class Italy_Cookie_Choices_Admin{

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
         * Inizialize banner template to default
         * @var string
         */
        private $js_template = 'default';

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
         * Initialize plugin
         * @return [type] [description]
         */
        public function italy_cl_settings_init() {

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
             * Checkbox for open in new page
             */
            add_settings_field( 
                'secondView', 
                __( 'Accept on second view', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_secondView'), 
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
             * Select box for js_template selection
             */
            add_settings_field( 
                'js_template', 
                __( 'CookieChoices Template', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_js_template'), 
                'italy_cl_options_group', 
                'style_setting_section'
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
                __( 'Advanced settings', 'italy-cookie-choices' ), 
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
             * Function for custom script allow
             */
            add_settings_field( 
                'custom_script_block_body_exclude', 
                __( 'Scripts allowed in body', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_custom_script_block_body_exclude'), 
                'italy_cl_options_group', 
                'advanced_setting_section'
                );

            /**
             * Function for custom script block
             */
            add_settings_field( 
                'custom_script_block', 
                __( 'Scripts to be blocked', 'italy-cookie-choices' ), 
                array( $this, 'italy_cl_option_custom_script_block'), 
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
                <?php _e( 'Top Bar (Default, Display a top bar with your message)', 'italy-cookie-choices' ); ?>
            </label>

            <br>

            <input name="italy_cookie_choices[banner]" type="radio" value="2" id="radio_2" <?php checked( '2', $banner ); ?> />

            <label for="radio_2">
                <?php _e( 'Dialog (Display an overlay with your message)', 'italy-cookie-choices' ); ?>
            </label>
        
        <br>
        
    <input name="italy_cookie_choices[banner]" type="radio" value="3" id="radio_3" <?php checked( '3', $banner ); ?> />

            <label for="radio_3">
                <?php _e( 'Bottom Bar (Display a bar in the footer with your message)', 'italy-cookie-choices' ); ?>
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
         * Snippet for second view checkbox $secondView
         * @return strimg       Activate for accept on second view 
         *                      Default do nothing
         */
        public function italy_cl_option_secondView($args) {

            $secondView = ( isset( $this->options['secondView'] ) ) ? $this->options['secondView'] : '' ;

        ?>

            <input type='checkbox' name='italy_cookie_choices[secondView]' <?php checked( $secondView, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[secondView]">
                <?php _e( 'Activate accept on second view', 'italy-cookie-choices' ); ?>
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
                <?php _e( 'Refresh page after button click (DEPRECATED)', 'italy-cookie-choices' ); ?>
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
                <?php _e( 'Add a page top margin for info top bar, only for default topbar stile', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }
    
    /**
         * Snippet for select
         * @return strimg       Chose the JS_Template to use.
         */
        public function italy_cl_option_js_template($args) {

            $js_template = ( isset( $this->options['js_template'] ) ) ? $this->options['js_template'] : $this->js_template ;

        ?>
            <select  name='italy_cookie_choices[js_template]'>
                <option value="default" <?php if ($js_template === 'default') echo 'selected';?>>Default cookiechoices template (centered with text links)</option>
                <option value="bigbutton" <?php if ($js_template === 'bigbutton') echo 'selected';?>>Centered container with left aligned text and big buttons</option>
                <option value="smallbutton" <?php if ($js_template === 'smallbutton') echo 'selected';?>>Centered container with left aligned text and small buttons</option>
                <!--<option value="custom" <?php if ($js_template === 'default') echo 'selected';?>>Custom CSS</option>-->
            </select>
            <label for="italy_cookie_choices[js_template]">
                <?php _e( 'Select the template to use', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Snippet for checkbox
         * @return strimg       Activate banner in front-end Default doesn't display
         */
        public function italy_cl_option_banner_bg($args) {

            $banner_bg = ( isset( $this->options['banner_bg'] ) ) ? $this->options['banner_bg'] : '#fff' ;

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

            $banner_text_color = ( isset( $this->options['banner_text_color'] ) ) ? $this->options['banner_text_color'] : '#000' ;

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

            $all_block = ( isset( $this->options['all_block'] ) ) ? $this->options['all_block'] : '' ;

            $block = ( isset( $this->options['block'] ) && $all_block === '' ) ? $this->options['block'] : '' ;

            $widget_block = ( isset( $this->options['widget_block'] ) && $all_block === '' ) ? $this->options['widget_block'] : '' ;

        ?>

            <input type='checkbox' name='italy_cookie_choices[block]' <?php checked( $block, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[block]">
                <?php _e( 'Cookie from any embed in your content (Beta) (DEPRECATED)', 'italy-cookie-choices' ); ?>
            </label>
            <br>
            <input type='checkbox' name='italy_cookie_choices[widget_block]' <?php checked( $widget_block, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[widget_block]">
                <?php _e( 'Cookie from any embed in your widget area (Beta) (DEPRECATED)', 'italy-cookie-choices' ); ?>
            </label>
            <br>
            <input type='checkbox' name='italy_cookie_choices[all_block]' <?php checked( $all_block, 1 ); ?> value='1'>
            <label for="italy_cookie_choices[all_block]">
                <?php _e( 'Cookie from any embed in all body, except head and footer (Beta)', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Textarea for custom_script_block_body_exclude
         * Scripts allowed in body
         * @return string
         */
        public function italy_cl_option_custom_script_block_body_exclude($args) {

            $custom_script_block_body_exclude = ( isset( $this->options['custom_script_block_body_exclude'] ) ) ? $this->options['custom_script_block_body_exclude'] : '' ;

        ?>
            <textarea rows="5" cols="70" name="italy_cookie_choices[custom_script_block_body_exclude]" id="italy_cookie_choices[custom_script_block_body_exclude]" placeholder="<?php _e( '&lt;script src=&quot;http://domain.com/widget-example.js&quot;&gt;&lt;/script&gt;'."\n".'&lt;---------SEP---------&gt;'."\n".'&lt;script src=&quot;http://otherdomain.com/script-example.js&quot;&gt;&lt;/script&gt;'."\n".'&lt;---------SEP---------&gt;'."\n".'&lt;script src=&quot;http://lastdomain.com/gadget-example.js&quot;&gt;&lt;/script&gt;', 'italy-cookie-choices' ) ?>" ><?php echo esc_textarea( $custom_script_block_body_exclude ); ?></textarea>
            <br>
            <label for="italy_cookie_choices[custom_script_block_body_exclude]">
                <?php echo __( 'Scripts to be excluded from the automatic block.<br />Split each script with <strong><em>&lt;---------SEP---------&gt;</em></strong>', 'italy-cookie-choices' ); ?>
            </label>
        <?php

        }

        /**
         * Textarea for content_message_text
         * @return string
         */
        public function italy_cl_option_custom_script_block($args) {

            $custom_script_block = ( isset( $this->options['custom_script_block'] ) ) ? $this->options['custom_script_block'] : '' ;

        ?>
            <textarea rows="5" cols="70" name="italy_cookie_choices[custom_script_block]" id="italy_cookie_choices[custom_script_block]" placeholder="<?php _e( '&lt;script src=&quot;http://domain.com/widget-example.js&quot;&gt;&lt;/script&gt;'."\n".'&lt;---------SEP---------&gt;'."\n".'&lt;script src=&quot;http://otherdomain.com/script-example.js&quot;&gt;&lt;/script&gt;'."\n".'&lt;---------SEP---------&gt;'."\n".'&lt;script src=&quot;http://lastdomain.com/gadget-example.js&quot;&gt;&lt;/script&gt;', 'italy-cookie-choices' ) ?>" ><?php echo esc_textarea( $custom_script_block ); ?></textarea>
            <br>
            <label for="italy_cookie_choices[custom_script_block]">
                <?php echo __( 'Scripts shown in the head and in the footer does not automatically blocked.<br />Split each script with <strong><em>&lt;---------SEP---------&gt;</em></strong>', 'italy-cookie-choices' ); ?>
            </label>

        <?php

        }

        /**
         * Function for custom script block
         * @return string
         */
        public function italy_cl_option_content_message_text($args) {

            $content_message_text = ( isset( $this->options['content_message_text'] ) ) ? $this->options['content_message_text'] : '' ;

        ?>
            <textarea rows="5" cols="70" name="italy_cookie_choices[content_message_text]" id="italy_cookie_choices[content_message_text]" placeholder="<?php _e( 'Your lock message for embedded contents inside posts, pages and widgets', 'italy-cookie-choices' ) ?>" ><?php echo esc_textarea( $content_message_text ); ?></textarea>
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

            $content_message_button_text = ( isset( $this->options['content_message_button_text'] ) ) ? $this->options['content_message_button_text'] : '' ;

        ?>
            <input type="text" id="italy_cookie_choices[content_message_button_text]" name="italy_cookie_choices[content_message_button_text]" value="<?php echo esc_attr( $content_message_button_text ); ?>" placeholder="<?php _e( 'e.g. Close', 'italy-cookie-choices' ) ?>" />

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

        	// require 'class-italy-cookie-choices-sanitize.php';

        	// new Italy_Cookie_Choices_Sanitize( $input );

            $new_input = array();

            if( isset( $input['active'] ) )
                $new_input['active'] =  $input['active'];

            if( isset( $input['banner'] ) )
                $new_input['banner'] =  $input['banner'];

            if( isset( $input['scroll'] ) )
                $new_input['scroll'] =  $input['scroll'];

            if( isset( $input['secondView'] ) )
                $new_input['secondView'] =  $input['secondView'];

            if( isset( $input['reload'] ) )
                $new_input['reload'] =  $input['reload'];

            if( isset( $input['text'] ) )
                $new_input['text'] = sanitize_text_field( $input['text'] );

            /**
             * Multilingual
             */
            register_string( 'Italy Cookie Choices', 'Banner text', $new_input['text'] );

            if( isset( $input['url'] ) )
                $new_input['url'] = sanitize_text_field( $input['url'] );

            // register_string( 'Italy Cookie Choices', 'Banner url', $new_input['url'] );

            if( isset( $input['anchor_text'] ) )
                $new_input['anchor_text'] = sanitize_text_field( $input['anchor_text'] );

            register_string( 'Italy Cookie Choices', 'Banner anchor text', $new_input['anchor_text'] );

            if( isset( $input['button_text'] ) )
                $new_input['button_text'] = sanitize_text_field( $input['button_text'] );

            register_string( 'Italy Cookie Choices', 'Banner button text', $new_input['button_text'] );

            /**
             * Sezione per lo stile
             */
            if( isset( $input['html_margin'] ) )
                $new_input['html_margin'] =  $input['html_margin'];
        
            if( isset( $input['js_template'] ) )
                $new_input['js_template'] =  $input['js_template'];
        
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

            if( isset( $input['all_block'] ) )
                $new_input['all_block'] =  $input['all_block'];

            if( isset( $input['custom_script_block_body_exclude'] ) )
                $new_input['custom_script_block_body_exclude'] =  $input['custom_script_block_body_exclude'];

            if( isset( $input['custom_script_block'] ) )
                $new_input['custom_script_block'] =  $input['custom_script_block'];

            if( isset( $input['content_message_text'] ) )
                $new_input['content_message_text'] =  sanitize_text_field( $input['content_message_text'] );
        
            if( isset( $input['content_message_button_text'] ) )
                $new_input['content_message_button_text'] =  sanitize_text_field( $input['content_message_button_text'] );

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

    }// class
}//endif