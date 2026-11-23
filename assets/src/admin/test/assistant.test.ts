import { byKey, parseSuggestions } from '../assistant';

describe( 'parseSuggestions', () => {
	it( 'reads the suggestions and skips the invalid ones', () => {
		const host = {
			host: 'cdn.example',
			name: 'CDN',
			category: 'marketing',
			purpose: 'Ads.',
			vendor: '',
		};
		const cookie = {
			name: 'lang',
			provider: 'Site',
			category: 'necessary',
			purpose: 'Language.',
		};

		expect(
			parseSuggestions( {
				hosts: [ host, { host: 'x' }, 'text' ],
				cookies: [ cookie, { name: 1 } ],
			} )
		).toEqual( { hosts: [ host ], cookies: [ cookie ] } );
	} );

	it( 'returns no suggestion for an invalid reply', () => {
		expect( parseSuggestions( null ) ).toEqual( {
			hosts: [],
			cookies: [],
		} );
	} );
} );

describe( 'byKey', () => {
	it( 'indexes the suggestions by a field', () => {
		const first = { name: 'a', purpose: 'A' };
		const second = { name: 'b', purpose: 'B' };

		expect( byKey( [ first, second ], 'name' ) ).toEqual( {
			a: first,
			b: second,
		} );
	} );
} );
