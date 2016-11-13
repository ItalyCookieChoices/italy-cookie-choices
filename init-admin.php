<?php
/**
 * Init plugin admin panel
 *
 * Manage the plugin admin panel init
 *
 * @since 2.5.0
 *
 * @package Italy_Cookie_Choices
 */

namespace Italy_Cookie_Choices\Admin;

if ( ! is_admin() ) {
	return;
}

$icc_settings = new Settings();
