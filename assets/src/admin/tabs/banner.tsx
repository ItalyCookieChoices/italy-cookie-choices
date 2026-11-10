/* @jsxRuntime classic */
/* @jsx createElement */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	SelectField,
	TextField,
	ToggleField,
	type SettingsProps,
} from '../fields';
import { isChecked } from '../settings';
import {
	AcceptButtonTextField,
	BannerTextField,
	FloatingButtonLabelField,
	PolicyLinkFields,
} from '../texts';

export function BannerTab( props: SettingsProps ) {
	return (
		<Fragment>
			<ToggleField
				{ ...props }
				name="active"
				label={ __( 'Show the banner', 'italy-cookie-choices' ) }
				help={ __(
					'The banner asks the consent and the third party content waits for it.',
					'italy-cookie-choices'
				) }
			/>
			<BannerTextField { ...props } />
			<PolicyLinkFields { ...props } />
			<ToggleField
				{ ...props }
				name="target"
				label={ __(
					'Open the policy in a new tab',
					'italy-cookie-choices'
				) }
			/>
			<ToggleField
				{ ...props }
				name="nofollow"
				label={ __(
					'Add rel="nofollow" to the policy link',
					'italy-cookie-choices'
				) }
			/>
			<AcceptButtonTextField { ...props } />
			<TextField
				{ ...props }
				name="excluded_pages"
				label={ __(
					'Pages where the banner does not open by itself',
					'italy-cookie-choices'
				) }
				help={ __(
					'IDs or slugs, separated by commas. The visitors can still open the preferences there.',
					'italy-cookie-choices'
				) }
			/>
			<TextField
				{ ...props }
				name="cookie_domain"
				label={ __( 'Cookie domain', 'italy-cookie-choices' ) }
				help={ __(
					'Empty for the domain of the site only. Write the main domain, like example.com, to share the consent with its subdomains, like www.example.com and shop.example.com. A domain the site is not part of is not saved.',
					'italy-cookie-choices'
				) }
				placeholder="example.com"
			/>
			<ToggleField
				{ ...props }
				name="reload"
				label={ __(
					'Reload the page after the choice',
					'italy-cookie-choices'
				) }
			/>
			<ToggleField
				{ ...props }
				name="global_privacy_control"
				label={ __(
					'Respect the Global Privacy Control of the browser',
					'italy-cookie-choices'
				) }
				help={ __(
					'When the browser of the visitor asks not to share the data, accept all leaves out the marketing cookies. The visitor can still choose them in the preferences.',
					'italy-cookie-choices'
				) }
			/>
			<ToggleField
				{ ...props }
				name="floating_button"
				label={ __(
					'Show a floating button to change the consent',
					'italy-cookie-choices'
				) }
				help={ __(
					'After the choice, a small button in a corner of the page opens the preferences, without adding the block or the link to the site.',
					'italy-cookie-choices'
				) }
			/>
			{ isChecked( props.settings, 'floating_button' ) && (
				<Fragment>
					<SelectField
						{ ...props }
						name="floating_button_position"
						label={ __(
							'Position of the floating button',
							'italy-cookie-choices'
						) }
						options={ [
							{
								value: 'left',
								label: __(
									'Bottom left',
									'italy-cookie-choices'
								),
							},
							{
								value: 'right',
								label: __(
									'Bottom right',
									'italy-cookie-choices'
								),
							},
						] }
						defaultValue="left"
					/>
					<FloatingButtonLabelField { ...props } />
				</Fragment>
			) }
		</Fragment>
	);
}
