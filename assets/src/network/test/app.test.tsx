/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { App } from '../app';
import type { NetworkData } from '../data';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	saveNetwork: jest.fn(),
	copySettings: jest.fn(),
} ) );

const mocked = jest.mocked( api );

const DATA: NetworkData = {
	shared: false,
	mainSiteId: 1,
	sites: [
		{ id: 1, name: 'Main', url: 'https://example.com/' },
		{ id: 2, name: 'Italiano', url: 'https://example.com/it/' },
		{ id: 3, name: 'Deutsch', url: 'https://example.com/de/' },
	],
};

beforeEach( () => jest.resetAllMocks() );

function notice( text: string ): HTMLElement {
	return screen.getByText( text, {
		selector: '.components-notice__content',
	} );
}

function copyAndConfirm(): void {
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Copy the settings' } )
	);
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Replace the settings' } )
	);
}

it( 'saves the switch of the main site', async () => {
	mocked.saveNetwork.mockResolvedValue( { shared: true } );
	render( <App data={ DATA } /> );

	fireEvent.click(
		screen.getByLabelText(
			'Use the settings of the main site on every site'
		)
	);
	fireEvent.click( screen.getByRole( 'button', { name: 'Save' } ) );

	await waitFor( () => expect( notice( 'Settings saved.' ) ).toBeTruthy() );
	expect( mocked.saveNetwork ).toHaveBeenCalledWith( true );
} );

it( 'hides the copy while the sites use the main site', () => {
	render( <App data={ { ...DATA, shared: true } } /> );

	expect(
		screen.queryByRole( 'button', { name: 'Copy the settings' } )
	).toBeNull();
	expect(
		screen.getByText(
			'The sites use the settings of the main site, there is nothing to copy.'
		)
	).toBeTruthy();
} );

it( 'copies the settings of a site to the chosen sites', async () => {
	mocked.copySettings.mockResolvedValue( [ 2, 3 ] );
	render( <App data={ DATA } /> );

	expect(
		(
			screen.getByRole( 'button', {
				name: 'Copy the settings',
			} ) as HTMLButtonElement
		 ).disabled
	).toBe( true );
	fireEvent.click( screen.getByLabelText( 'Italiano' ) );
	fireEvent.click( screen.getByLabelText( 'Deutsch' ) );
	fireEvent.click( screen.getByLabelText( 'Copy the texts too' ) );
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Copy the settings' } )
	);
	expect(
		screen.getByText( 'The settings of 2 sites will be replaced.' )
	).toBeTruthy();
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Replace the settings' } )
	);

	await waitFor( () =>
		expect( notice( 'The settings were copied to 2 sites.' ) ).toBeTruthy()
	);
	expect( mocked.copySettings ).toHaveBeenCalledWith( 1, [ 2, 3 ], true );
} );

it( 'does not list the source among the targets', () => {
	render( <App data={ DATA } /> );

	fireEvent.change( screen.getByLabelText( 'Copy from' ), {
		target: { value: '2' },
	} );

	expect( screen.queryByLabelText( 'Italiano' ) ).toBeNull();
	expect( screen.getByLabelText( 'Main' ) ).toBeTruthy();
} );

it( 'does not copy without the confirmation', () => {
	render( <App data={ DATA } /> );

	fireEvent.click( screen.getByLabelText( 'Italiano' ) );
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Copy the settings' } )
	);
	fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

	expect( mocked.copySettings ).not.toHaveBeenCalled();
	expect(
		screen.getByRole( 'button', { name: 'Copy the settings' } )
	).toBeTruthy();
} );

it( 'shows why the copy failed', async () => {
	mocked.copySettings.mockRejectedValue( {
		message: 'The sites use the settings of the main site.',
	} );
	render( <App data={ DATA } /> );

	fireEvent.click( screen.getByLabelText( 'Italiano' ) );
	copyAndConfirm();

	await waitFor( () =>
		expect(
			notice( 'The sites use the settings of the main site.' )
		).toBeTruthy()
	);
} );
