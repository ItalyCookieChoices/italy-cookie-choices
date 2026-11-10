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

	it( 'reads the address that renews the consent cookie', () => {
		expect(
			parseConfig( { renewalUrl: 'https://example.com/consent-cookie' } )
				.renewalUrl
		).toBe( 'https://example.com/consent-cookie' );
	} );

	it( 'reads the domain of the consent cookie', () => {
		expect(
			parseConfig( { cookieDomain: 'example.com' } ).cookieDomain
		).toBe( 'example.com' );
		expect(
			parseConfig( { cookieDomain: '' } ).cookieDomain
		).toBeUndefined();
		expect(
			parseConfig( { cookieDomain: 42 } ).cookieDomain
		).toBeUndefined();
	} );

	it.each( [ [ '' ], [ 42 ], [ undefined ] ] )(
		'does not renew the consent cookie without an address, %p',
		( renewalUrl ) => {
			expect( parseConfig( { renewalUrl } ).renewalUrl ).toBeUndefined();
		}
	);

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
