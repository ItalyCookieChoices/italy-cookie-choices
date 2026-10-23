/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { App } from '../app';
import { data } from './fixtures/data';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	loadSettings: jest.fn(),
	saveSettings: jest.fn(),
	previewHtml: jest.fn(),
} ) );

const mocked = jest.mocked( api );

// The notices are also spoken in a live region.
const NOTICE = { selector: '.components-notice__content' };

function saveButton(): HTMLButtonElement {
	const button = screen.getByRole( 'button', { name: 'Save changes' } );
	if ( ! ( button instanceof HTMLButtonElement ) ) {
		throw new Error( 'The save button is not a button' );
	}

	return button;
}

beforeEach( () => {
	jest.resetAllMocks();
	mocked.loadSettings.mockResolvedValue( {
		active: '1',
		text: 'Saved text',
	} );
	mocked.previewHtml.mockResolvedValue( '<p>Preview</p>' );
} );

it( 'saves the changed settings', async () => {
	mocked.saveSettings.mockImplementation( ( settings ) =>
		Promise.resolve( settings )
	);
	render( <App data={ data } /> );

	const text = await screen.findByLabelText( 'Banner text' );
	expect( saveButton().disabled ).toBe( true );

	fireEvent.change( text, { target: { value: 'Changed text' } } );
	expect( saveButton().disabled ).toBe( false );
	fireEvent.click( saveButton() );

	expect( await screen.findByText( 'Settings saved.', NOTICE ) ).toBeTruthy();
	expect( mocked.saveSettings ).toHaveBeenCalledWith( {
		active: '1',
		text: 'Changed text',
	} );
	expect( saveButton().disabled ).toBe( true );
} );

it( 'shows why the settings were not saved', async () => {
	mocked.saveSettings.mockRejectedValue( {
		message: 'Invalid parameter(s): italy_cookie_choices',
	} );
	render( <App data={ data } /> );

	fireEvent.click(
		await screen.findByRole( 'checkbox', { name: 'Show the banner' } )
	);
	fireEvent.click( saveButton() );

	expect(
		await screen.findByText(
			'Invalid parameter(s): italy_cookie_choices',
			NOTICE
		)
	).toBeTruthy();
	expect( saveButton().disabled ).toBe( false );
} );

it( 'shows an error when the settings are not loaded', async () => {
	mocked.loadSettings.mockRejectedValue( new Error( '' ) );
	render( <App data={ data } /> );

	expect(
		await screen.findByText( 'The settings were not loaded.', NOTICE )
	).toBeTruthy();
	expect(
		screen.queryByRole( 'button', { name: 'Save changes' } )
	).toBeNull();
} );

it( 'previews the settings not saved yet', async () => {
	render( <App data={ data } /> );

	fireEvent.change( await screen.findByLabelText( 'Banner text' ), {
		target: { value: 'Preview text' },
	} );

	await waitFor( () =>
		expect( mocked.previewHtml ).toHaveBeenLastCalledWith(
			{ active: '1', text: 'Preview text' },
			'banner'
		)
	);
	fireEvent.click( screen.getByRole( 'button', { name: 'Preferences' } ) );
	await waitFor( () =>
		expect( mocked.previewHtml ).toHaveBeenLastCalledWith(
			{ active: '1', text: 'Preview text' },
			'preferences'
		)
	);
	expect(
		screen.getByTitle( 'Banner preview' ).getAttribute( 'srcdoc' )
	).toBe( '<p>Preview</p>' );
} );
