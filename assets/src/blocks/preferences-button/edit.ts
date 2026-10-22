import { RichText, useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { PreferencesButtonAttributes } from './types';

/**
 * The label is edited in place, the server prints the button with the default label when it is empty.
 *
 * Without JSX: its automatic runtime needs the react-jsx-runtime script, added in WordPress 6.6.
 * @param props               The block props.
 * @param props.attributes    The block attributes.
 * @param props.setAttributes Saves the attributes.
 */
export function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< PreferencesButtonAttributes > ) {
	const blockProps = useBlockProps( {
		className: 'icc-preferences-button wp-element-button',
	} );

	return createElement(
		'div',
		blockProps,
		createElement( RichText, {
			tagName: 'span',
			value: attributes.label,
			allowedFormats: [],
			withoutInteractiveFormatting: true,
			placeholder: __( 'Cookie preferences', 'italy-cookie-choices' ),
			'aria-label': __( 'Button label', 'italy-cookie-choices' ),
			onChange: ( label: string ) => setAttributes( { label } ),
		} )
	);
}
