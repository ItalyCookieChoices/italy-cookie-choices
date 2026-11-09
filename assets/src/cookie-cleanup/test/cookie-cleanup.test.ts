import { createConsentApi } from '../../consent/api';
import { Cookies } from '../../consent/cookies';
import type { ConsentApi, ConsentConfig } from '../../consent/types';
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
};

function consentApi(): ConsentApi {
	return createConsentApi(
		consentConfig,
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
		).toEqual( { categories: { statistics: [ '_ga' ] } } );
		expect( parseCookieCleanupConfig( null ) ).toEqual( {
			categories: {},
		} );
	} );
} );

describe( 'cookiesToDelete', () => {
	it( 'finds the cookies of the refused categories, by name and by prefix', () => {
		expect(
			cookiesToDelete(
				config,
				[ 'necessary', 'marketing' ],
				[ '_ga', '_ga_ABC', '_gid', '_fbp', 'icc_consent' ]
			)
		).toEqual( [ '_ga', '_ga_ABC' ] );
	} );

	it( 'finds nothing when every category is accepted', () => {
		expect(
			cookiesToDelete(
				config,
				[ 'necessary', 'statistics', 'marketing' ],
				[ '_ga', '_fbp' ]
			)
		).toEqual( [] );
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

	it( 'deletes the cookies of every category on a withdrawal', () => {
		const api = consentApi();
		api.acceptAll();
		startCookieCleanup( config, api, document, 'localhost' );
		setCookies( '_ga', '_fbp' );

		api.withdraw();

		expect( cookieNames() ).toEqual( [] );
	} );
} );
