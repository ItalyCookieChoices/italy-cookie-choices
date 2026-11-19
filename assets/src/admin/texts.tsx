/* @jsxRuntime classic */
/* @jsx createElement */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextareaField, TextField, type SettingsProps } from './fields';
import { LinkPage } from './link-page';
import { LINK_SETTINGS, type BannerLink } from './settings';

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

type LinkProps = SettingsProps & { link: BannerLink };

/**
 * The address and the text of a link of the banner, translated by each site and language.
 * @param props The settings props and the link.
 */
export function LinkAddressFields( props: LinkProps ) {
	const { link, ...settingsProps } = props;
	const names = LINK_SETTINGS[ link ];
	const isPolicy = link === 'policy';

	return (
		<Fragment>
			<TextField
				{ ...settingsProps }
				name={ names.url }
				type="url"
				label={
					isPolicy
						? __( 'Policy link', 'italy-cookie-choices' )
						: __( 'Legal notice link', 'italy-cookie-choices' )
				}
			/>
			<TextField
				{ ...settingsProps }
				name={ names.text }
				label={
					isPolicy
						? __( 'Policy link text', 'italy-cookie-choices' )
						: __( 'Legal notice link text', 'italy-cookie-choices' )
				}
				help={ __(
					'The link shows its address when it is empty.',
					'italy-cookie-choices'
				) }
			/>
		</Fragment>
	);
}

/**
 * The page of a link of the banner, its address and its text.
 * @param props The settings props and the link.
 */
export function LinkFields( props: LinkProps ) {
	return (
		<Fragment>
			<LinkPage { ...props } />
			<LinkAddressFields { ...props } />
		</Fragment>
	);
}

/**
 * The texts of the buttons of the banner and of the preferences, empty for the default text in the placeholder.
 * @param props The settings props.
 */
export function ButtonTextFields( props: SettingsProps ) {
	return (
		<Fragment>
			<TextField
				{ ...props }
				name="button_text"
				label={ __( 'Accept button text', 'italy-cookie-choices' ) }
				placeholder={ __( 'Accept all', 'italy-cookie-choices' ) }
			/>
			<TextField
				{ ...props }
				name="reject_button_text"
				label={ __( 'Reject button text', 'italy-cookie-choices' ) }
				placeholder={ __( 'Reject all', 'italy-cookie-choices' ) }
			/>
			<TextField
				{ ...props }
				name="preferences_button_text"
				label={ __(
					'Preferences button text',
					'italy-cookie-choices'
				) }
				placeholder={ __( 'Preferences', 'italy-cookie-choices' ) }
			/>
			<TextField
				{ ...props }
				name="save_button_text"
				label={ __( 'Save button text', 'italy-cookie-choices' ) }
				help={ __(
					'The button of the preferences that saves the choice.',
					'italy-cookie-choices'
				) }
				placeholder={ __( 'Save preferences', 'italy-cookie-choices' ) }
			/>
		</Fragment>
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
