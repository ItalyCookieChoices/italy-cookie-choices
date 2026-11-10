/* @jsxRuntime classic */
/* @jsx createElement */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextareaField, TextField, type SettingsProps } from './fields';
import { PolicyPage } from './policy-page';

/**
 * The texts each site of a network keeps when it uses the settings of the main site,
 * see Schema::SITE_TEXTS in src/Settings/Schema.php.
 */

export function BannerTextField( props: SettingsProps ) {
	return (
		<TextareaField
			{ ...props }
			name="text"
			label={ __( 'Banner text', 'italy-cookie-choices' ) }
			help={ __(
				'Links and simple HTML are allowed.',
				'italy-cookie-choices'
			) }
		/>
	);
}

export function PolicyLinkFields( props: SettingsProps ) {
	return (
		<Fragment>
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
		</Fragment>
	);
}

export function AcceptButtonTextField( props: SettingsProps ) {
	return (
		<TextField
			{ ...props }
			name="button_text"
			label={ __( 'Accept button text', 'italy-cookie-choices' ) }
			placeholder={ __( 'Accept all', 'italy-cookie-choices' ) }
		/>
	);
}

export function FloatingButtonLabelField( props: SettingsProps ) {
	return (
		<TextField
			{ ...props }
			name="floating_button_label"
			label={ __(
				'Label of the floating button',
				'italy-cookie-choices'
			) }
			help={ __(
				'Read by the screen readers and shown when the pointer is over the button.',
				'italy-cookie-choices'
			) }
			placeholder={ __( 'Cookie preferences', 'italy-cookie-choices' ) }
		/>
	);
}

export function PlaceholderTextFields( props: SettingsProps ) {
	return (
		<Fragment>
			<TextareaField
				{ ...props }
				name="content_message_text"
				label={ __(
					'Placeholder text of the blocked content',
					'italy-cookie-choices'
				) }
				help={ __(
					'Empty for a text with the category name.',
					'italy-cookie-choices'
				) }
			/>
			<TextField
				{ ...props }
				name="content_message_button_text"
				label={ __(
					'Placeholder button text',
					'italy-cookie-choices'
				) }
			/>
		</Fragment>
	);
}
