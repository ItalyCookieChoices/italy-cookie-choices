import { createConsentApi } from '../../consent/api';
import { Cookies } from '../../consent/cookies';
import {
	CONSENT_CHANGED,
	type ConsentApi,
	type ConsentChange,
	type ConsentConfig,
} from '../../consent/types';
import {
	cookiesToDelete,
	domainsOf,
	parseCookieCleanupConfig,
	startCookieCleanup,
	type CookieCleanupConfig,
} from '../cookie-cleanup';

const consentConfig: ConsentConfig = {
	cookieName: 'icc_consent',
	categories: [
		{ id: 'necessary', required: true },
		{ id: 'statistics', required: false },
		{ id: 'marketing', required: false },
	],
	version: '1',
	durationDays: 10,
	legacyCookie: null,
};

const config: CookieCleanupConfig = {
	categories: {
		statistics: [ '_ga', '_ga_*' ],
		marketing: [ '_fbp', '_gcl_*' ],
	},
	vendors: [],
};

const vendorConfig: CookieCleanupConfig = {
	categories: {
		statistics: [ '_ga', '_ga_*', '_trk' ],
		marketing: [ '_fbp', '_gcl_*' ],
	},
	vendors: [
		{
			id: 'google-analytics',
			category: 'statistics',
			cookies: [ '_ga', '_ga_*' ],
		},
		{ id: 'tracker', category: 'statistics', cookies: [ '_trk', '_ga' ] },
		{ id: 'facebook', category: 'marketing', cookies: [ '_fbp' ] },
		{ id: 'google-ads', category: 'marketing', cookies: [ '_gcl_*' ] },
	],
};

function consentApi( vendorConsent = false ): ConsentApi {
	return createConsentApi(
		vendorConsent
			? {
					...consentConfig,
					vendorConsent,
					vendors: vendorConfig.vendors.map(
						( { id, category } ) => ( {
							id,
							category,
						} )
					),
				}
			: consentConfig,
		new Cookies( document, false ),
		document
	);
}

function cookieNames(): string[] {
	return document.cookie
		.split( '; ' )
		.filter( ( cookie ) => cookie !== '' )
		.map( ( cookie ) => cookie.split( '=' )[ 0 ] ?? '' )
		.sort();
}

function setCookies( ...names: string[] ): void {
	names.forEach( ( name ) => {
		document.cookie = `${ name }=1; path=/`;
	} );
}

afterEach( () => {
	cookieNames().forEach( ( name ) => {
		document.cookie = `${ name }=; expires=${ new Date( 0 ).toUTCString() }; path=/`;
	} );
} );

describe( 'parseCookieCleanupConfig', () => {
	it( 'reads the names of each category', () => {
		expect( parseCookieCleanupConfig( config ) ).toEqual( config );
	} );

	it( 'keeps only the lists of strings', () => {
		expect(
			parseCookieCleanupConfig( {
				categories: { statistics: [ '_ga', 1 ], marketing: 'x' },
			} )
		).toEqual( { categories: { statistics: [ '_ga' ] }, vendors: [] } );
		expect( parseCookieCleanupConfig( null ) ).toEqual( {
			categories: {},
			vendors: [],
		} );
	} );

	it( 'reads the cookies of each vendor, the invalid vendors are skipped', () => {
		expect( parseCookieCleanupConfig( vendorConfig ) ).toEqual(
			vendorConfig
		);
		expect(
			parseCookieCleanupConfig( {
				categories: {},
				vendors: [
					{ id: 'a', category: 'statistics', cookies: [ '_a', 2 ] },
					{ id: 'b', category: 'statistics' },
					{ id: 3, category: 'statistics', cookies: [] },
					'c',
				],
			} )
		).toEqual( {
			categories: {},
			vendors: [ { id: 'a', category: 'statistics', cookies: [ '_a' ] } ],
		} );
	} );
} );

describe( 'cookiesToDelete', () => {
	it( 'finds the cookies of the refused categories, by name and by prefix', () => {
		expect(
			cookiesToDelete(
				config,
				{ categories: [ 'necessary', 'marketing' ] },
				[ '_ga', '_ga_ABC', '_gid', '_fbp', 'icc_consent' ]
			)
		).toEqual( [ '_ga', '_ga_ABC' ] );
	} );

	it( 'finds nothing when every category is accepted', () => {
		expect(
			cookiesToDelete(
				config,
				{ categories: [ 'necessary', 'statistics', 'marketing' ] },
				[ '_ga', '_fbp' ]
			)
		).toEqual( [] );
	} );

	it( 'finds the cookies of the vendors refused inside an accepted category', () => {
		expect(
			cookiesToDelete(
				vendorConfig,
				{
					categories: [ 'necessary', 'statistics', 'marketing' ],
					refusedVendors: [ 'google-analytics', 'google-ads' ],
				},
				[ '_ga_ABC', '_gcl_au', '_fbp', '_trk' ]
			)
		).toEqual( [ '_ga_ABC', '_gcl_au' ] );
	} );

	it( 'keeps a cookie a vendor still accepted shares with a refused one', () => {
		expect(
			cookiesToDelete(
				vendorConfig,
				{
					categories: [ 'necessary', 'statistics' ],
					refusedVendors: [ 'google-analytics' ],
				},
				[ '_ga', '_ga_ABC', '_trk' ]
			)
		).toEqual( [ '_ga_ABC' ] );
	} );

	it( 'deletes a shared cookie when its other vendor is in a refused category', () => {
		expect(
			cookiesToDelete(
				{
					categories: { statistics: [ '_x' ], marketing: [] },
					vendors: [
						{ id: 'a', category: 'statistics', cookies: [ '_x' ] },
						{ id: 'b', category: 'marketing', cookies: [ '_x' ] },
					],
				},
				{
					categories: [ 'necessary', 'statistics' ],
					refusedVendors: [ 'a' ],
				},
				[ '_x' ]
			)
		).toEqual( [ '_x' ] );
	} );
} );

describe( 'domainsOf', () => {
	it( 'lists the host and its parent domains', () => {
		expect( domainsOf( 'www.shop.example.com' ) ).toEqual( [
			'www.shop.example.com',
			'.www.shop.example.com',
			'.shop.example.com',
			'.example.com',
		] );
	} );

	it( 'keeps a host without parents as it is', () => {
		expect( domainsOf( 'localhost' ) ).toEqual( [ 'localhost' ] );
	} );
} );

describe( 'startCookieCleanup', () => {
	it( 'deletes the cookies of the categories without a consent on load', () => {
		setCookies( '_ga', '_ga_ABC', '_fbp', 'theme' );

		startCookieCleanup( config, consentApi(), document, 'localhost' );

		expect( cookieNames() ).toEqual( [ 'theme' ] );
	} );

	it( 'keeps the cookies of the accepted categories', () => {
		const api = consentApi();
		api.setConsent( [ 'statistics' ] );
		setCookies( '_ga', '_fbp' );

		startCookieCleanup( config, api, document, 'localhost' );

		expect( cookieNames() ).toEqual( [ '_ga', 'icc_consent' ] );
	} );

	it( 'deletes the cookies of a category refused later', () => {
		const api = consentApi();
		api.acceptAll();
		startCookieCleanup( config, api, document, 'localhost' );
		setCookies( '_ga', '_gcl_au' );

		api.setConsent( [ 'marketing' ] );

		expect( cookieNames() ).toEqual( [ '_gcl_au', 'icc_consent' ] );
	} );

	it( 'deletes the cookies of a vendor refused later', () => {
		const api = consentApi( true );
		api.acceptAll();
		startCookieCleanup( vendorConfig, api, document, 'localhost' );
		setCookies( '_ga', '_ga_ABC', '_trk' );

		api.setConsent( [ 'statistics' ], [ 'tracker' ] );

		expect( cookieNames() ).toEqual( [ '_ga', '_ga_ABC', 'icc_consent' ] );
	} );

	it( 'deletes the cookies of a vendor refused in another tab', () => {
		const api = consentApi( true );
		api.acceptAll();
		startCookieCleanup( vendorConfig, api, document, 'localhost' );
		setCookies( '_trk' );
		const previous = api.getConsent();

		document.dispatchEvent(
			new CustomEvent< ConsentChange >( CONSENT_CHANGED, {
				detail: {
					consent: previous && {
						...previous,
						categories: [ 'necessary', 'statistics' ],
						refusedVendors: [ 'tracker' ],
					},
					previous,
					fromAnotherTab: true,
				},
			} )
		);

		expect( cookieNames() ).toEqual( [ 'icc_consent' ] );
	} );

	it( 'deletes the cookies of every category on a withdrawal', () => {
		const api = consentApi();
		api.acceptAll();
		startCookieCleanup( config, api, document, 'localhost' );
		setCookies( '_ga', '_fbp' );

		api.withdraw();

		expect( cookieNames() ).toEqual( [] );
	} );
} );
