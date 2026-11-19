/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { ButtonTextFields } from '../texts';

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
