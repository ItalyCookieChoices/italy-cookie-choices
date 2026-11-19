/* @jsxRuntime classic */
/* @jsx createElement */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CustomScriptList } from '../custom-scripts';
import { TextField, ToggleField, type SettingsProps } from '../fields';
import { isChecked, type Category } from '../settings';

interface IntegrationsTabProps extends SettingsProps {
	categories: readonly Category[];
	/** Whether the user can save unfiltered HTML, so the custom scripts. */
	canEditScripts: boolean;
}

/**
 * Google Consent Mode v2, the Microsoft consent signals, the Google and Facebook tags printed by the plugin,
 * the custom scripts and the YouTube embeds.
 * @param props The settings props, the categories and whether the user can edit the scripts.
 */
export function IntegrationsTab( props: IntegrationsTabProps ) {
	const { categories, canEditScripts, ...settingsProps } = props;
	return (
		<Fragment>
			<fieldset className="icc-admin__group">
				<legend>
					{ __( 'Google Consent Mode v2', 'italy-cookie-choices' ) }
				</legend>
				<ToggleField
					{ ...settingsProps }
					name="consent_mode"
					label={ __(
						'Send the Consent Mode signals',
						'italy-cookie-choices'
					) }
					help={ __(
						'The Google tags get the consent of the visitor: denied until the choice, then granted for the accepted categories.',
						'italy-cookie-choices'
					) }
				/>
				{ isChecked( settingsProps.settings, 'consent_mode' ) && (
					<ToggleField
						{ ...settingsProps }
						name="consent_mode_advanced"
						label={ __(
							'Load the Google tags before the consent (advanced mode)',
							'italy-cookie-choices'
						) }
						help={ __(
							'Google Analytics and Google Tag Manager are not blocked, they send cookieless pings until the consent. Check that it is allowed where your visitors are, the Italian Garante requires the consent before any tracking.',
							'italy-cookie-choices'
						) }
					/>
				) }
			</fieldset>
			<fieldset className="icc-admin__group">
				<legend>
					{ __(
						'Microsoft consent signals',
						'italy-cookie-choices'
					) }
				</legend>
				<ToggleField
					{ ...settingsProps }
					name="uet_consent_mode"
					label={ __(
						'Send the consent to Microsoft Advertising (UET)',
						'italy-cookie-choices'
					) }
					help={ __(
						'The UET tag gets the consent of the visitor: the ad storage is denied until the marketing category is accepted.',
						'italy-cookie-choices'
					) }
				/>
				<ToggleField
					{ ...settingsProps }
					name="clarity_consent_mode"
					label={ __(
						'Send the consent to Microsoft Clarity',
						'italy-cookie-choices'
					) }
					help={ __(
						'Clarity gets the consent of the visitor with its Consent API v2: the analytics storage follows the statistics category, the ad storage the marketing category. Clarity requires it for the visitors from the EEA, the UK and Switzerland.',
						'italy-cookie-choices'
					) }
				/>
			</fieldset>
			<fieldset className="icc-admin__group">
				<legend>{ __( 'Tags', 'italy-cookie-choices' ) }</legend>
				<p>
					{ __(
						'The plugin prints the tags with the ids below, blocked until the consent. Leave a field empty when the tag is printed by the theme or by another plugin.',
						'italy-cookie-choices'
					) }
				</p>
				<TextField
					{ ...settingsProps }
					name="ga_measurement_id"
					label={ __(
						'Google Analytics measurement ID',
						'italy-cookie-choices'
					) }
					placeholder="G-XXXXXXXXXX"
				/>
				<TextField
					{ ...settingsProps }
					name="gtm_container_id"
					label={ __(
						'Google Tag Manager container ID',
						'italy-cookie-choices'
					) }
					placeholder="GTM-XXXXXXX"
				/>
				<TextField
					{ ...settingsProps }
					name="facebook_pixel_id"
					label={ __( 'Facebook pixel ID', 'italy-cookie-choices' ) }
					placeholder="123456789012345"
				/>
				<TextField
					{ ...settingsProps }
					name="google_tag_gateway_path"
					label={ __(
						'Google tag gateway path',
						'italy-cookie-choices'
					) }
					help={ __(
						'When the Google tags load from a path of your site with the Google tag gateway, write the path: the scripts under it wait for the marketing consent. The tags with their id in the address, like G-XXXXXXXXXX, are blocked anyway.',
						'italy-cookie-choices'
					) }
					placeholder="/metrics"
				/>
			</fieldset>
			<fieldset className="icc-admin__group">
				<legend>
					{ __(
						'Scripts after the consent',
						'italy-cookie-choices'
					) }
				</legend>
				<p>
					{ __(
						'Code the plugin adds to the pages after the consent to its category, like a chat or a tracking snippet that no vendor covers.',
						'italy-cookie-choices'
					) }
				</p>
				<CustomScriptList
					scripts={ settingsProps.settings.custom_scripts ?? [] }
					categories={ categories }
					canEdit={ canEditScripts }
					onChange={ ( scripts ) =>
						settingsProps.onChange( {
							...settingsProps.settings,
							custom_scripts: scripts,
						} )
					}
				/>
			</fieldset>
			<ToggleField
				{ ...settingsProps }
				name="youtube_nocookie"
				label={ __(
					'Use the privacy-enhanced mode of the YouTube embeds',
					'italy-cookie-choices'
				) }
				help={ __(
					'The videos load from youtube-nocookie.com, they still wait for the marketing consent.',
					'italy-cookie-choices'
				) }
			/>
		</Fragment>
	);
}
