/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { MigrationPanel } from '../migration';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	previewMigration: jest.fn(),
	importMigration: jest.fn(),
} ) );

const mocked = jest.mocked( api );
const SOURCES = [ { id: 'complianz', name: 'Complianz' } ];
const RESULT = {
	summary: [ 'The texts of the banner and of the buttons' ],
	notes: [ 'Deactivate Complianz after the import.' ],
};

beforeEach( () => jest.resetAllMocks() );

it( 'shows nothing without another plugin', () => {
	const { container } = render(
		<MigrationPanel
			sources={ [] }
			onImported={ jest.fn() }
			onError={ jest.fn() }
		/>
	);

	expect( container.innerHTML ).toBe( '' );
} );

it( 'previews the import of a plugin', async () => {
	mocked.previewMigration.mockResolvedValue( RESULT );
	render(
		<MigrationPanel
			sources={ SOURCES }
			onImported={ jest.fn() }
			onError={ jest.fn() }
		/>
	);

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Preview the import of Complianz',
		} )
	);

	expect(
		await screen.findByText( 'The texts of the banner and of the buttons' )
	).toBeTruthy();
	expect(
		screen.getByText( 'Deactivate Complianz after the import.' )
	).toBeTruthy();
	expect( mocked.previewMigration ).toHaveBeenCalledWith( 'complianz' );
} );

it( 'imports a plugin and shows what to check', async () => {
	mocked.importMigration.mockResolvedValue( {
		...RESULT,
		settings: { text: 'Imported' },
	} );
	const onImported = jest.fn();
	render(
		<MigrationPanel
			sources={ SOURCES }
			onImported={ onImported }
			onError={ jest.fn() }
		/>
	);

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Import the settings of Complianz',
		} )
	);

	await waitFor( () =>
		expect( onImported ).toHaveBeenCalledWith( { text: 'Imported' } )
	);
	expect(
		screen.getByText( 'Deactivate Complianz after the import.' )
	).toBeTruthy();
} );

it( 'tells when the import fails', async () => {
	mocked.importMigration.mockRejectedValue( {
		message: 'There are no settings of this plugin to import.',
	} );
	const onError = jest.fn();
	render(
		<MigrationPanel
			sources={ SOURCES }
			onImported={ jest.fn() }
			onError={ onError }
		/>
	);

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Import the settings of Complianz',
		} )
	);

	await waitFor( () =>
		expect( onError ).toHaveBeenCalledWith(
			'There are no settings of this plugin to import.'
		)
	);
} );
