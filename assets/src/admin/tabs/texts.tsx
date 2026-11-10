/* @jsxRuntime classic */
/* @jsx createElement */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { SettingsProps } from '../fields';
import type { Category } from '../settings';
import {
	AcceptButtonTextField,
	BannerTextField,
	FloatingButtonLabelField,
	PlaceholderTextFields,
	PolicyLinkFields,
} from '../texts';
import { CategoriesTab } from './categories';

/**
 * The texts of a site that uses the settings of the main site of the network, for its language.
 * @param props The settings props and the categories with their default texts.
 */
export function TextsTab(
	props: SettingsProps & { categories: readonly Category[] }
) {
	return (
		<Fragment>
			<BannerTextField { ...props } />
			<PolicyLinkFields { ...props } />
			<AcceptButtonTextField { ...props } />
			<FloatingButtonLabelField { ...props } />
			<PlaceholderTextFields { ...props } />
			<h3>{ __( 'Categories', 'italy-cookie-choices' ) }</h3>
			<CategoriesTab { ...props } />
		</Fragment>
	);
}
