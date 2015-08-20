<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Plugin_Name
 * @author    Your Name <email@example.com>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2015 Your Name or Company Name
 */

?>
<div class="wrap">

	<form action='options.php' method='post' id='italy-cookie-choices-ID'>

		<?php
		settings_fields( 'italy_cl_options_group' );
		do_settings_sections( 'italy_cl_options_group' );
		submit_button();
		?>

	</form>

	<div id="tabs-3" class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Export Settings'); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Export the plugin settings for this site as a .json file. This allows you to easily import the configuration into another site.' ); ?></p>
				<form method="post">
					<p>
						<input type="hidden" name="icc_action" value="export_settings" />
					</p>
					<p>
						<?php wp_nonce_field( 'icc_export_nonce', 'icc_export_nonce' ); ?>
						<?php submit_button( __( 'Export' ), 'secondary', 'submit', false ); ?>
					</p>
				</form>
			</div>
		</div>

		<div class="postbox">
			<h3 class="hndle"><span><?php _e( 'Import Settings' ); ?></span></h3>
			<div class="inside">
				<p><?php _e( 'Import the plugin settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.' ); ?></p>
				<form method="post" enctype="multipart/form-data">
					<p>
						<input type="file" name="icc_import_file"/>
					</p>
					<p>
						<input type="hidden" name="icc_action" value="import_settings" />
						<?php wp_nonce_field( 'icc_import_nonce', 'icc_import_nonce' ); ?>
						<?php submit_button( __( 'Import' ), 'secondary', 'submit', false ); ?>
					</p>
				</form>
			</div>
		</div>
	</div>
</div>