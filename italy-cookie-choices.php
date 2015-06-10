<?php
/**
 * Plugin Name: Italy Cookie Choices
 * Plugin URI: https://plus.google.com/u/0/communities/109254048492234113886
 * Description: Italy Cookie Choices allows you to easily comply with the european cookie law and block third part cookie in your page.
 * Version: 2.1.0
 * Author: Enea Overclokk, Andrea Pernici, Andrea Cardinale
 * Author URI: https://github.com/ItalyCookieChoices/italy-cookie-choices
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
    define('ITALY_COOKIE_CHOICES_PLUGIN_PATH', plugin_dir_path( ITALY_COOKIE_CHOICES_FILE ) );
/**
 * Example = italystrap-extended/italystrap.php
 */
if ( !defined( 'ITALY_COOKIE_CHOICES_BASENAME' ) )
    define('ITALY_COOKIE_CHOICES_BASENAME', plugin_basename( ITALY_COOKIE_CHOICES_FILE ) );

/**
 * Example = F:\xampp\htdocs\italystrap\wp-content\plugins\italy-cookie-choices
 */
if ( !defined( 'ITALY_COOKIE_CHOICES_DIRNAME' ) )
    define('ITALY_COOKIE_CHOICES_DIRNAME', dirname( ITALY_COOKIE_CHOICES_FILE ) );

/**
 * Require PHP files
 */
require(ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'admin/class-italy-cookie-choices-admin.php');

require(ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'classes/class-italy-cookie-choices-front-end.php');
require(ITALY_COOKIE_CHOICES_PLUGIN_PATH . 'classes/class-italy-cookie-choices-lang.php');

/**
 * Initialize plugin
 * Functions for check version come from sz-google
 */

if ( ! class_exists( 'Italy_Cookie_Choices' ) ) {

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
        private $WP_ver = '4.1';
        
        /**
         * Make some magics
         */
        public function __construct(){

            /**
             * Check if is compatible and then instantiate it
             */
            if ( $this->is_compatible_version() && is_admin() )
                new Italy_Cookie_Choices_Admin;

            else if ( $this->is_compatible_version() && !is_admin() )
                new Italy_Cookie_Choices_Front_End;

            else
                add_action( 'admin_notices', array( $this, 'load_plugin_admin_notices' ) );

            /**
             * Check compatibility on install
             */
            register_activation_hook(__FILE__,array($this,'check_compatibility_on_install'));

            /**
             * adjust priority to make sure this runs
             */
            add_action( 'init', array( $this, 'init'), 100 );

            
        }

        /**
         * Check if plugin is compatible, if it is not then it wont activate
         * @return string Return error message in case plugin is not compatible
         */
        function check_compatibility_on_install(){

            if ( !$this->is_compatible_version() ) {

                $HTML  = '<div>' . __( 'Activation of Italy Cookie Choices in not possible', 'italy-cookie-choices' ) . ':</div><ul>';

                if ( !$this->is_compatible_PHP() )
                    $HTML .= '<li>' . $this->get_admin_notices_PHP( false ) . '</li>';

                if ( !$this->is_compatible_WORDPRESS() )
                    $HTML .= '<li>' . $this->get_admin_notices_WORDPRESS( false ) . '</li>';

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
         * Checking compatibility with installed versions of the plugin
         * In case of incompatibility still fully loaded plugin (return)
         * @return boolean Check if plugin is compatible
         */
        function is_compatible_version() {

            if ( $this->is_compatible_PHP() && $this->is_compatible_WORDPRESS() )
                return true;
            else
                return false;
        }

        /**
         * Checking the compatibility of the plugin with the version of PHP
         * In case of incompatibility still fully loaded plugin (return)
         * @return boolean Check PHP compatibility
         */
        function is_compatible_PHP() {

            if ( version_compare( phpversion(), $this->PHP_ver, '<') )
                return false;
            else
                return true;

        }

        /**
         * Checking the compatibility of the plugin with the version of Wordpress
         * In case of incompatibility still fully loaded plugin (return)
         * @return boolean Check WordPress compatibility
         */
        function is_compatible_WORDPRESS() {

            if ( version_compare( $GLOBALS['wp_version'], $this->WP_ver, '<') )
                return false;
            else
                return true;
        }

        /**
         * If the plugin is active, but the minimum requirements are not met
         * the function is called to add the details on the notice board error
         * @return string Print error message
         */
        function load_plugin_admin_notices() {

            if ( !$this->is_compatible_PHP() )
                echo $this->get_admin_notices_PHP( true );

            if ( !$this->is_compatible_WORDPRESS() )
                echo $this->get_admin_notices_WORDPRESS( true );
        }

        function get_admin_notices_PHP( $wrap ) {

            return $this->get_admin_notices_TEXT( $wrap, 'PHP', phpversion(), $this->PHP_ver );
        }

        function get_admin_notices_WORDPRESS( $wrap ) {

            return $this->get_admin_notices_TEXT( $wrap, 'WordPress', $GLOBALS['wp_version'], $this->WP_ver);
        }

        /**
         * A function that creates a generic error to be displayed during 
         * the activation function or on the bulletin board of directors.
         * @param  bolean $wrap [description]
         * @param  string $s1   PHP or WordPress
         * @param  string $s2   Current version
         * @param  string $s3   Required version
         * @return string       Display errors
         */
        function get_admin_notices_TEXT( $wrap, $s1, $s2, $s3 ) {

            $HTML = __( 'Your server is running %s version %s but this plugin requires at least %s', 'italy-cookie-choices' );

            if ( $wrap === false )
                $HTML = '<div>' . $HTML . '</div>';

            else
                $HTML = '<div class="error"><p><b>Italy Cookie Choices</b> - ' . $HTML . '</p></div>';

            return sprintf( $HTML, $s1, $s2, $s3 );
        }





    } // End Italy_Cookie_Choices

    new Italy_Cookie_Choices;
}