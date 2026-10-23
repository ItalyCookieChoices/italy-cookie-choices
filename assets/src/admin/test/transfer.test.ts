import { exportFile, exportFileName, readJsonFile } from '../transfer';

describe( 'the export', () => {
	it( 'has the version of the schema', () => {
		expect( exportFile( { active: '1' } ) ).toEqual( {
			plugin: 'italy-cookie-choices',
			version: 5,
			settings: { active: '1' },
		} );
	} );

	it( 'is named after the date', () => {
		expect( exportFileName( new Date( '2026-10-23T10:00:00Z' ) ) ).toBe(
			'italy-cookie-choices-settings-2026-10-23.json'
		);
	} );
} );

function file( text: string ): Pick< Blob, 'text' > {
	return { text: () => Promise.resolve( text ) };
}

describe( 'readJsonFile', () => {
	it( 'reads the JSON of the file', async () => {
		await expect(
			readJsonFile( file( '[{"active":"1"}]' ) )
		).resolves.toEqual( [ { active: '1' } ] );
	} );

	it( 'reads null when the file is not JSON', async () => {
		await expect( readJsonFile( file( 'not json' ) ) ).resolves.toBeNull();
	} );
} );
