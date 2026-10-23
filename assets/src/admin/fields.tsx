/* @jsxRuntime classic */
/* @jsx createElement */
import {
	SelectControl,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	isChecked,
	textOf,
	withCheckbox,
	type Checkbox,
	type Settings,
	type Text,
} from './settings';

/**
 * The fields bound to a key of the option.
 */
export interface SettingsProps {
	settings: Settings;
	onChange: ( settings: Settings ) => void;
}

interface FieldProps< Name > extends SettingsProps {
	name: Name;
	label: string;
	help?: string;
}

export function ToggleField( {
	settings,
	onChange,
	name,
	label,
	help,
}: FieldProps< Checkbox > ) {
	return (
		<ToggleControl
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			checked={ isChecked( settings, name ) }
			onChange={ ( checked: boolean ) =>
				onChange( withCheckbox( settings, name, checked ) )
			}
		/>
	);
}

interface TextFieldProps extends FieldProps< Text > {
	type?: 'text' | 'url';
	placeholder?: string;
}

export function TextField( {
	settings,
	onChange,
	name,
	label,
	help,
	type = 'text',
	placeholder,
}: TextFieldProps ) {
	return (
		<TextControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			type={ type }
			label={ label }
			help={ help }
			placeholder={ placeholder }
			value={ textOf( settings, name ) }
			onChange={ ( value: string ) =>
				onChange( { ...settings, [ name ]: value } )
			}
		/>
	);
}

export function TextareaField( {
	settings,
	onChange,
	name,
	label,
	help,
}: FieldProps< Text > ) {
	return (
		<TextareaControl
			__nextHasNoMarginBottom
			label={ label }
			help={ help }
			value={ textOf( settings, name ) }
			onChange={ ( value: string ) =>
				onChange( { ...settings, [ name ]: value } )
			}
		/>
	);
}

interface SelectFieldProps extends FieldProps< Text > {
	options: Array< { value: string; label: string } >;
	defaultValue: string;
}

export function SelectField( {
	settings,
	onChange,
	name,
	label,
	help,
	options,
	defaultValue,
}: SelectFieldProps ) {
	const value = textOf( settings, name );

	return (
		<SelectControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ label }
			help={ help }
			options={ options }
			value={ value !== '' ? value : defaultValue }
			onChange={ ( selected: string ) =>
				onChange( { ...settings, [ name ]: selected } )
			}
		/>
	);
}
