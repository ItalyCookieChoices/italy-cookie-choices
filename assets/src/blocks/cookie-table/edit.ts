import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * The table depends on the vendors and the cookies of the settings, the server prints it on the page.
 *
 * Without JSX: its automatic runtime needs the react-jsx-runtime script, added in WordPress 6.6.
 */
export function Edit() {
	return createElement(
		'div',
		useBlockProps(),
		createElement( Placeholder, {
			icon: 'list-view',
			label: __( 'Cookie table', 'italy-cookie-choices' ),
			instructions: __(
				'The cookies of the site by category, with their provider, purpose and duration. The table is printed on the page, from the vendors and the cookies of the settings.',
				'italy-cookie-choices'
			),
		} )
	);
}
