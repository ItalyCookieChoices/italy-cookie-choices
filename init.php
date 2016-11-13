<?php
/**
 * Init plugin
 *
 * Manage the plugin init
 *
 * @since 2.5.0
 *
 * @package Italy_Cookie_Choices
 */

namespace Italy_Cookie_Choices\Core;

if ( is_admin() ) {
	return;
}

if ( is_bot() ) {
	return;
}

if ( is_sitemaps_xml() ) {
	return;
}

$icc_cookie_choices = new Cookie_Choices();
