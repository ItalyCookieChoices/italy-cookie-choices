/* @jsxRuntime classic */
/* @jsx createElement */
import { BaseControl, ColorPalette, PanelBody } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	SelectField,
	TextareaField,
	TextField,
	ToggleField,
	type SettingsProps,
} from '../fields';
import { textOf, type Text } from '../settings';

const TOP = '1';

function ColorField( {
	settings,
	onChange,
	name,
	label,
}: SettingsProps & { name: Text; label: string } ) {
	const id = `icc-admin-${ name }`;

	return (
		<BaseControl id={ id } label={ label } __nextHasNoMarginBottom>
			<ColorPalette
				colors={ [] }
				clearable={ false }
				value={ textOf( settings, name ) }
				onChange={ ( color?: string ) =>
					onChange( { ...settings, [ name ]: color ?? '' } )
				}
			/>
		</BaseControl>
	);
}

export function StyleTab( props: SettingsProps ) {
	const position = textOf( props.settings, 'banner' );

	return (
		<Fragment>
			<SelectField
				{ ...props }
				name="banner"
				label={ __( 'Position', 'italy-cookie-choices' ) }
				defaultValue={ TOP }
				options={ [
					{
						value: '1',
						label: __( 'Top bar', 'italy-cookie-choices' ),
					},
					{
						value: '2',
						label: __( 'Modal window', 'italy-cookie-choices' ),
					},
					{
						value: '3',
						label: __( 'Bottom bar', 'italy-cookie-choices' ),
					},
				] }
			/>
			{ ( position === '' || position === TOP ) && (
				<ToggleField
					{ ...props }
					name="html_margin"
					label={ __(
						'Push the page down under the top bar',
						'italy-cookie-choices'
					) }
				/>
			) }
			<SelectField
				{ ...props }
				name="js_template"
				label={ __( 'Template', 'italy-cookie-choices' ) }
				defaultValue="default"
				help={ __(
					'The custom template has no styles, only the custom CSS.',
					'italy-cookie-choices'
				) }
				options={ [
					{
						value: 'default',
						label: __( 'Default', 'italy-cookie-choices' ),
					},
					{
						value: 'bigbutton',
						label: __( 'Big buttons', 'italy-cookie-choices' ),
					},
					{
						value: 'smallbutton',
						label: __( 'Small buttons', 'italy-cookie-choices' ),
					},
					{
						value: 'custom',
						label: __( 'Custom', 'italy-cookie-choices' ),
					},
				] }
			/>
			<ColorField
				{ ...props }
				name="banner_bg"
				label={ __( 'Background color', 'italy-cookie-choices' ) }
			/>
			<ColorField
				{ ...props }
				name="banner_text_color"
				label={ __( 'Text color', 'italy-cookie-choices' ) }
			/>
			<TextareaField
				{ ...props }
				name="customCSS"
				label={ __( 'Custom CSS', 'italy-cookie-choices' ) }
			/>
			<PanelBody
				title={ __( 'CSS classes', 'italy-cookie-choices' ) }
				initialOpen={ false }
			>
				<TextField
					{ ...props }
					name="bannerStyle"
					label={ __( 'Banner', 'italy-cookie-choices' ) }
				/>
				<TextField
					{ ...props }
					name="contentStyle"
					label={ __( 'Content', 'italy-cookie-choices' ) }
				/>
				<TextField
					{ ...props }
					name="consentText"
					label={ __( 'Text', 'italy-cookie-choices' ) }
				/>
				<TextField
					{ ...props }
					name="infoClass"
					label={ __( 'Policy link', 'italy-cookie-choices' ) }
				/>
				<TextField
					{ ...props }
					name="closeClass"
					label={ __( 'Buttons', 'italy-cookie-choices' ) }
				/>
			</PanelBody>
		</Fragment>
	);
}
