/* @jsxRuntime classic */
/* @jsx createElement */
import {
	Button,
	CheckboxControl,
	FormTokenField,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	TextareaField,
	TextField,
	ToggleField,
	type SettingsProps,
} from '../fields';
import {
	isVendorBlocked,
	newCustomVendor,
	withCustomVendor,
	withVendorBlocked,
	type Category,
	type CustomVendor,
	type Vendor,
} from '../settings';

interface BlockingTabProps extends SettingsProps {
	categories: readonly Category[];
	vendors: readonly Vendor[];
}

function tokens(
	values: ReadonlyArray< string | { value: string } >
): string[] {
	return values
		.map( ( token ) => ( typeof token === 'string' ? token : token.value ) )
		.map( ( token ) => token.trim() )
		.filter( ( token ) => token !== '' );
}

function CustomVendorFields( {
	vendor,
	categories,
	onChange,
}: {
	vendor: CustomVendor;
	categories: readonly Category[];
	onChange: ( vendor: CustomVendor | null ) => void;
} ) {
	return (
		<fieldset className="icc-admin__group">
			<legend>
				{ vendor.name !== ''
					? vendor.name
					: __( 'New vendor', 'italy-cookie-choices' ) }
			</legend>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Name', 'italy-cookie-choices' ) }
				value={ vendor.name }
				onChange={ ( name: string ) => onChange( { ...vendor, name } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Category', 'italy-cookie-choices' ) }
				value={ vendor.category }
				options={ categories.map( ( category ) => ( {
					value: category.id,
					label: category.label,
				} ) ) }
				onChange={ ( category: string ) =>
					onChange( { ...vendor, category } )
				}
			/>
			<FormTokenField
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __(
					'Addresses and scripts to block',
					'italy-cookie-choices'
				) }
				value={ vendor.patterns }
				onChange={ ( patterns ) =>
					onChange( { ...vendor, patterns: tokens( patterns ) } )
				}
			/>
			<p className="components-form-token-field__help">
				{ __(
					'A part of the address, like tracker.example.com/script.js, or a text of the inline script. Press Enter after each one.',
					'italy-cookie-choices'
				) }
			</p>
			<Button
				variant="secondary"
				isDestructive
				onClick={ () => onChange( null ) }
			>
				{ sprintf(
					/* translators: %s: the vendor name. */
					__( 'Remove %s', 'italy-cookie-choices' ),
					vendor.name !== ''
						? vendor.name
						: __( 'the vendor', 'italy-cookie-choices' )
				) }
			</Button>
		</fieldset>
	);
}

/**
 * The vendors whose scripts and frames wait for the consent, and the placeholder of the blocked frames.
 * @param props The tab props.
 */
export function BlockingTab( props: BlockingTabProps ) {
	const { settings, onChange, vendors } = props;
	const categories = props.categories.filter(
		( category ) => ! category.required
	);
	const labels = new Map(
		props.categories.map( ( category ) => [ category.id, category.label ] )
	);
	const customVendors = settings.custom_vendors ?? [];

	return (
		<Fragment>
			<fieldset className="icc-admin__group">
				<legend>
					{ __( 'Common vendors', 'italy-cookie-choices' ) }
				</legend>
				{ vendors.map( ( vendor ) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={ vendor.id }
						label={ vendor.name }
						help={ sprintf(
							/* translators: %s: the category name. */
							__(
								'Waits for the %s consent.',
								'italy-cookie-choices'
							),
							labels.get( vendor.category ) ?? vendor.category
						) }
						checked={ isVendorBlocked( settings, vendor.id ) }
						onChange={ ( checked: boolean ) =>
							onChange(
								withVendorBlocked(
									settings,
									vendor.id,
									checked
								)
							)
						}
					/>
				) ) }
			</fieldset>
			<h3>{ __( 'Custom vendors', 'italy-cookie-choices' ) }</h3>
			{ customVendors.map( ( vendor, index ) => (
				<CustomVendorFields
					key={ vendor.id }
					vendor={ vendor }
					categories={ categories }
					onChange={ ( changed ) =>
						onChange( withCustomVendor( settings, index, changed ) )
					}
				/>
			) ) }
			<Button
				variant="secondary"
				disabled={ categories.length === 0 }
				onClick={ () =>
					onChange( {
						...settings,
						custom_vendors: [
							...customVendors,
							newCustomVendor(
								customVendors,
								categories[ categories.length - 1 ]?.id ?? ''
							),
						],
					} )
				}
			>
				{ __( 'Add a vendor', 'italy-cookie-choices' ) }
			</Button>
			<FormTokenField
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Never blocked', 'italy-cookie-choices' ) }
				value={ settings.allowed_patterns ?? [] }
				onChange={ ( patterns ) =>
					onChange( {
						...settings,
						allowed_patterns: tokens( patterns ),
					} )
				}
			/>
			<p className="components-form-token-field__help">
				{ __(
					'The addresses and the scripts the site needs, even when a vendor matches them.',
					'italy-cookie-choices'
				) }
			</p>
			<ToggleField
				{ ...props }
				name="all_block"
				label={ __(
					'Block the vendors in the whole page',
					'italy-cookie-choices'
				) }
				help={ __(
					'Also in the theme and in the plugins that do not use the WordPress functions. It buffers the page, turn it on only when needed.',
					'italy-cookie-choices'
				) }
			/>
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
