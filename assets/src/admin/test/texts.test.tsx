/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { ButtonTextFields, LinkAddressFields } from '../texts';

it.each( [
	[ 'Accept button text', 'button_text', 'Accept all' ],
	[ 'Reject button text', 'reject_button_text', 'Reject all' ],
	[ 'Preferences button text', 'preferences_button_text', 'Preferences' ],
	[ 'Save button text', 'save_button_text', 'Save preferences' ],
] )(
	'edits the %s, the default text is the placeholder',
	( label, name, placeholder ) => {
		const onChange = jest.fn();
		render( <ButtonTextFields settings={ {} } onChange={ onChange } /> );

		const field = screen.getByRole( 'textbox', { name: label } );
		expect( field.getAttribute( 'placeholder' ) ).toBe( placeholder );
		fireEvent.change( field, { target: { value: 'Custom' } } );

		expect( onChange ).toHaveBeenCalledWith( { [ name ]: 'Custom' } );
	}
);

it( 'edits the address and the text of the legal notice link', () => {
	const onChange = jest.fn();
	render(
		<LinkAddressFields
			link="legal-notice"
			settings={ {} }
			onChange={ onChange }
		/>
	);

	fireEvent.change(
		screen.getByRole( 'textbox', { name: 'Legal notice link' } ),
		{ target: { value: 'https://example.com/impressum/' } }
	);
	expect( onChange ).toHaveBeenLastCalledWith( {
		legal_notice_url: 'https://example.com/impressum/',
	} );

	fireEvent.change(
		screen.getByRole( 'textbox', { name: 'Legal notice link text' } ),
		{ target: { value: 'Impressum' } }
	);
	expect( onChange ).toHaveBeenLastCalledWith( {
		legal_notice_text: 'Impressum',
	} );
} );
