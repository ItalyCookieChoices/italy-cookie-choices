/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { ToolsTab } from '../tabs/tools';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	importSettings: jest.fn(),
} ) );

const mocked = jest.mocked( api );

function chooseFile( text: string ): void {
	const file = new File( [ text ], 'settings.json', {
		type: 'application/json',
	} );
	Object.defineProperty( file, 'text', {
		value: () => Promise.resolve( text ),
	} );
	fireEvent.change( screen.getByLabelText( 'Settings file' ), {
		target: { files: [ file ] },
	} );
}

function renderTab() {
	const callbacks = { onImported: jest.fn(), onError: jest.fn() };
	render(
		<ToolsTab
			settings={ {} }
			onChange={ jest.fn() }
			saved={ { active: '1' } }
			{ ...callbacks }
		/>
	);

	return callbacks;
}

beforeEach( () => jest.resetAllMocks() );

it( 'imports the settings of a file', async () => {
	mocked.importSettings.mockResolvedValue( {
		active: '1',
		text: 'Imported',
	} );
	const { onImported } = renderTab();

	chooseFile( '[{"active":"1"}]' );

	await waitFor( () =>
		expect( onImported ).toHaveBeenCalledWith( {
			active: '1',
			text: 'Imported',
		} )
	);
	expect( mocked.importSettings ).toHaveBeenCalledWith( [ { active: '1' } ] );
} );

it( 'rejects a file that is not JSON', async () => {
	const { onError, onImported } = renderTab();

	chooseFile( 'not json' );

	await waitFor( () =>
		expect( onError ).toHaveBeenCalledWith(
			'The file is not a valid JSON file.'
		)
	);
	expect( mocked.importSettings ).not.toHaveBeenCalled();
	expect( onImported ).not.toHaveBeenCalled();
} );

it( 'shows why the server rejected the file', async () => {
	mocked.importSettings.mockRejectedValue( {
		message: 'The file has no settings of Italy Cookie Choices.',
	} );
	const { onError } = renderTab();

	chooseFile( '{}' );

	await waitFor( () =>
		expect( onError ).toHaveBeenCalledWith(
			'The file has no settings of Italy Cookie Choices.'
		)
	);
} );
