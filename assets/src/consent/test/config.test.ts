import { parseConfig } from '../config';

describe( 'parseConfig', () => {
	it( 'reads a valid configuration', () => {
		expect(
			parseConfig( {
				cookieName: 'consent',
				categories: [
					{ id: 'necessary', required: true },
					{ id: 'marketing' },
				],
				version: '2',
				durationDays: 30,
				legacyCookie: { name: 'displayCookieConsent', value: 'y' },
			} )
		).toEqual( {
			cookieName: 'consent',
			categories: [
				{ id: 'necessary', required: true },
				{ id: 'marketing', required: false },
			],
			version: '2',
			durationDays: 30,
			legacyCookie: { name: 'displayCookieConsent', value: 'y' },
		} );
	} );

	it.each( [ [ undefined ], [ null ], [ 'config' ] ] )(
		'uses the defaults for %p',
		( raw ) => {
			expect( parseConfig( raw ) ).toEqual( {
				cookieName: 'icc_consent',
				categories: [],
				version: '1',
				durationDays: 182,
				legacyCookie: null,
			} );
		}
	);

	it( 'replaces the invalid values with the defaults', () => {
		expect(
			parseConfig( {
				cookieName: '',
				categories: [ { id: '' }, 'marketing', { id: 'statistics' } ],
				version: 2,
				durationDays: 0,
				legacyCookie: { name: '', value: 'y' },
			} )
		).toEqual( {
			cookieName: 'icc_consent',
			categories: [ { id: 'statistics', required: false } ],
			version: '1',
			durationDays: 182,
			legacyCookie: null,
		} );
	} );
} );
