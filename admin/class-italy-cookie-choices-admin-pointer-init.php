<?php

// Load and initialize class. If you're loading the Italy_Cookie_Choices_Pointer class in another plugin or theme, this is all you need.
require(ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'admin/class-italy-cookie-choices-admin-pointer.php');

if ( is_admin() )
	$pointerplus = new Italy_Cookie_Choices_Pointer( array( 'prefix' => 'italy-cookie-choices' ) );

// With this you can reset all the pointer with your prefix
// $pointerplus->reset_pointer();

///////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////// Everything after this point is only for pointerplus configuration ////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Some useful link
 * @link http://wordimpress.com/create-wordpress-theme-activation-popup-message/
 * @link http://www.wpexplorer.com/making-themes-plugins-more-usable/
 * @link http://code.tutsplus.com/articles/integrating-with-wordpress-ui-admin-pointers--wp-26853
 * @link http://premium.wpmudev.org/blog/using-wordpress-pointers-in-your-own-plugins/?utm_expid=3606929-40.lszTaIEzTbifDhvhVdd39A.0&utm_referrer=https%3A%2F%2Fwww.google.it%2F
 */

if ( !class_exists( 'Italy_Cookie_Choices_Pointer_Init' ) ) {

	/**
	* 
	*/
	class Italy_Cookie_Choices_Pointer_Init{
		
		function __construct(){

			// Your prefix
			add_filter( 'italy-cookie-choices' . '-pointerplus_list', array( $this, 'custom_initial_pointers'), 10, 2);

		}


		/**
		 * Add pointers.
		 *
		 * @param $pointers
		 * @param $prefix for your pointers
		 *
		 * @return mixed
		 */
		function custom_initial_pointers( $pointers, $prefix ) {

		     // * Default parameters:
		      // $defaults = array(
		      // 'class' => 'pointerplus',
		      // 'width' => 300, //fixed value
		      // 'align' => 'middle',
		      // 'edge' => 'left',
		      // 'post_type' => array(),
		      // 'pages' => array(),
		      // 'jsnext' => '', //empty [t = pointer instance, $ = jQuery]
		      // 'phpcode' => function(){}, //executed on admin_notices action
		      // 'show' => 'open' //default
		      // );
		     

			return array_merge( $pointers, array(
				$prefix . '_settings' => array(
					'selector' => '#active',
					'title' => __( 'Radio 1', 'italy-cookie-choices' ),
					'text' => __( 'The plugin is active and ready to start working.', 'italy-cookie-choices' ),
					'width' => 260,
					'icon_class' => 'dashicons-admin-settings',
					'jsnext' => "button = jQuery('<a id=\"pointer-close\" class=\"button action\">" . __( 'Next' ) . "</a>');
						button.bind('click.pointer', function () {
							t.element.pointer('close');
							jQuery('#label_radio_1').pointer('open');
						});
						return button;",
					// 'phpcode' => $this->custom_phpcode_thickbox( 'https://www.youtube.com/embed/EaWfDuXQfo0' )
				),
				$prefix . '_settings1' => array(
					'selector' => '#label_radio_1',
					'title' => __( 'Radio 1', 'italy-cookie-choices' ),
					'text' => __( 'The plugin is active and ready to start working.', 'italy-cookie-choices' ),
					'width' => 260,
					'icon_class' => 'dashicons-admin-settings',
					// 'jsnext' => "button = jQuery('<a id=\"pointer-close\" class=\"button action\">" . __( 'Next' ) . "</a>');
					// 	button.bind('click.pointer', function () {
					// 		t.element.pointer('close');
					// 		jQuery('#contextual-help-link').pointer('open');
					// 	});
					// 	return button;",
					// 'phpcode' => $this->custom_phpcode_thickbox()
					'show' => 'close'
				),
		    	$prefix . '_settings11' => array(
					'selector' => '#label_radio_111',
					'title' => __( 'Radio 2', 'italy-cookie-choices' ),
					'text' => __( 'The plugin is active and ready to start working.', 'italy-cookie-choices' ),
					'width' => 260,
					'icon_class' => 'dashicons-admin-settings',
					'jsnext' => "button = jQuery('<a id=\"pointer-close\" class=\"button action thickbox\" href=\"#TB_inline?width=700&height=500&inlineId=menu-popup\">" . __( 'Open Popup' ) . "</a>');
						button.bind('click.pointer', function () {
							t.element.pointer('close');
		    			});
						return button;",
					'phpcode' => $this->custom_phpcode_thickbox( 'https://www.youtube.com/embed/EaWfDuXQfo0' )
		    	),
		        $prefix . '_posts' => array(
		            'selector' => '#radio_1',
		            'title' => __( 'Italy_Cookie_Choices_Pointer for Posts', 'italy-cookie-choices' ),
		            'text' => __( 'One more pointer.', 'italy-cookie-choices' ),
		            'post_type' => array( 'post' ),
		            'icon_class' => 'dashicons-admin-post',
		            'width' => 350,
		        ),
		        $prefix . '_pages' => array(
		            'selector' => '#menu-pages',
		            'title' => __( 'Italy_Cookie_Choices_Pointer Pages', 'italy-cookie-choices' ),
		            'text' => __( 'A pointer for pages.', 'italy-cookie-choices' ),
		            'post_type' => array( 'page' ),
		            'icon_class' => 'dashicons-admin-post'
		        ),
		        $prefix . '_users' => array(
		            'selector' => '#menu-users',
		            'title' => __( 'Italy_Cookie_Choices_Pointer Users', 'italy-cookie-choices' ),
		            'text' => __( 'A pointer for users.', 'italy-cookie-choices' ),
		            'pages' => array( 'users.php' ),
		            'icon_class' => 'dashicons-admin-users'
		        ),
		        $prefix . '_settings_tab1' => array(
		            'selector' => '#show-settings-link',
		            'title' => __( 'Italy_Cookie_Choices_Pointer Help', 'italy-cookie-choices' ),
		            'text' => __( 'A pointer with action.', 'italy-cookie-choices' ),
		            'edge' => 'top',
		            'align' => 'right',
		            'icon_class' => 'dashicons-welcome-learn-more',
		            'jsnext' => "button = jQuery('<a id=\"pointer-close\" class=\"button action\">" . __( 'Next' ) . "</a>');
		                    button.bind('click.pointer', function () {
		                        t.element.pointer('close');
		                        jQuery('#contextual-help-link').pointer('open');
		                    });
		                    return button;"
		        ),
		        /**
		         * $prefix . '_my_custom_id'
		         * per ogni pointer deve essere univoco
		         */
		        $prefix . '_contextual_tab1' => array(
		            'selector' => '#contextual-help-link', // Il selettore css dove appendere il pointer, può essere un ID o una classe CSS
		            'title' => __( 'Italy_Cookie_Choices_Pointer Help', 'italy-cookie-choices' ),
		            'text' => __( 'A pointer for help tab.<br>Go to Posts, Pages or Users for other pointers.', 'italy-cookie-choices' ),
		            'edge' => 'top',
		            'align' => 'right',
		            'icon_class' => 'dashicons-welcome-learn-more',
		            'show' => 'close' // Serve per non visualizzare il pointer nella pagina, utile per usarlo insieme al pulsante next
		        )
		            ) );
		}
		

		//Function created for support PHP =>5.2
		//You can use the anonymous function that are not supported by PHP 5.2
		/**
		 * 
		 * @link https://codex.wordpress.org/Javascript_Reference/ThickBox
		 * @return string Return modal fro thickbox
		 */
		function custom_phpcode_thickbox( $url = '' ) {
			add_thickbox();
			echo '<div id="menu-popup" style="display:none;">
			<p style="text-align: center;">
			This is my hidden content! It will appear in ThickBox when the link is clicked.
			<iframe width="560" height="315" src="' . $url . '" frameborder="0" allowfullscreen></iframe>
			</p>
			</div>';
		}

	}

}