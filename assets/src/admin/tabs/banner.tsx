/* @jsxRuntime classic */
/* @jsx createElement */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	TextareaField,
	TextField,
	ToggleField,
	type SettingsProps,
} from '../fields';
import { PolicyPage } from '../policy-page';

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
			<TextareaField
				{ ...props }
				name="text"
				label={ __( 'Banner text', 'italy-cookie-choices' ) }
				help={ __(
					'Links and simple HTML are allowed.',
					'italy-cookie-choices'
				) }
			/>
			<PolicyPage { ...props } />
			<TextField
				{ ...props }
				name="url"
				type="url"
				label={ __( 'Policy link', 'italy-cookie-choices' ) }
			/>
			<TextField
				{ ...props }
				name="anchor_text"
				label={ __( 'Policy link text', 'italy-cookie-choices' ) }
				help={ __(
					'The link shows its address when it is empty.',
					'italy-cookie-choices'
				) }
			/>
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
			<TextField
				{ ...props }
				name="button_text"
				label={ __( 'Accept button text', 'italy-cookie-choices' ) }
				placeholder={ __( 'Accept all', 'italy-cookie-choices' ) }
			/>
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
			<ToggleField
				{ ...props }
				name="reload"
				label={ __(
					'Reload the page after the choice',
					'italy-cookie-choices'
				) }
			/>
		</Fragment>
	);
}
