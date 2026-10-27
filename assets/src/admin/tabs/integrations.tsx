/* @jsxRuntime classic */
/* @jsx createElement */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextField, ToggleField, type SettingsProps } from '../fields';
import { isChecked } from '../settings';

/**
 * Google Consent Mode v2, the Google and Facebook tags printed by the plugin and the YouTube embeds.
 * @param props The settings props.
 */
export function IntegrationsTab( props: SettingsProps ) {
	return (
		<Fragment>
			<fieldset className="icc-admin__group">
				<legend>
					{ __( 'Google Consent Mode v2', 'italy-cookie-choices' ) }
				</legend>
				<ToggleField
					{ ...props }
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
				{ isChecked( props.settings, 'consent_mode' ) && (
					<ToggleField
						{ ...props }
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
				<legend>{ __( 'Tags', 'italy-cookie-choices' ) }</legend>
				<p>
					{ __(
						'The plugin prints the tags with the ids below, blocked until the consent. Leave a field empty when the tag is printed by the theme or by another plugin.',
						'italy-cookie-choices'
					) }
				</p>
				<TextField
					{ ...props }
					name="ga_measurement_id"
					label={ __(
						'Google Analytics measurement ID',
						'italy-cookie-choices'
					) }
					placeholder="G-XXXXXXXXXX"
				/>
				<TextField
					{ ...props }
					name="gtm_container_id"
					label={ __(
						'Google Tag Manager container ID',
						'italy-cookie-choices'
					) }
					placeholder="GTM-XXXXXXX"
				/>
				<TextField
					{ ...props }
					name="facebook_pixel_id"
					label={ __( 'Facebook pixel ID', 'italy-cookie-choices' ) }
					placeholder="123456789012345"
				/>
			</fieldset>
			<ToggleField
				{ ...props }
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
