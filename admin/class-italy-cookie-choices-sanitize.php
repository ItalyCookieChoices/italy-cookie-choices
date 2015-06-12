<?php
/**
 * Class for sanitizations
 */
if ( !class_exists( 'Italy_Cookie_Choices_Sanitize' ) ){

    class Italy_Cookie_Choices_Sanitize{

    	public function __construct( $input ){

    		$this->sanitize_callback( $input );

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

            if( isset( $input['secondView'] ) )
                $new_input['secondView'] =  $input['secondView'];

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

    }

}