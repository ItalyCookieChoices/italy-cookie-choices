import { createConsentApi } from '../../consent/api';
import { Cookies } from '../../consent/cookies';
import type { ConsentApi, ConsentConfig } from '../../consent/types';
import {
	CONSENT_TYPE_DEFINED,
	consentValues,
	parseWpConsentApiConfig,
	startWpConsentApi,
	type WpConsentApiConfig,
} from '../wp-consent-api';

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

const config: WpConsentApiConfig = {
	consentType: 'optin',
	mapping: {
		necessary: [ 'functional' ],
		statistics: [ 'statistics', 'statistics-anonymous' ],
		marketing: [ 'marketing' ],
	},
};

const DENIED = {
	functional: 'allow',
	statistics: 'deny',
	'statistics-anonymous': 'deny',
	marketing: 'deny',
};

function consentApi(): ConsentApi {
	return createConsentApi(
		consentConfig,
		new Cookies( document, false ),
		document
	);
}

function start( api: ConsentApi = consentApi() ): jest.Mock {
	const setConsent = jest.fn();
	startWpConsentApi( config, api, setConsent, window );

	return setConsent;
}

function values( setConsent: jest.Mock ): Record< string, string > {
	return Object.fromEntries( setConsent.mock.calls );
}

afterEach( () => {
	document.cookie = `icc_consent=; expires=${ new Date( 0 ).toUTCString() }; path=/`;
	delete window.wp_consent_type;
} );

describe( 'consentValues', () => {
	it( 'allows the categories of the API mapped from the accepted ones', () => {
		expect(
			consentValues( config.mapping, [ 'necessary', 'marketing' ] )
		).toEqual( { ...DENIED, marketing: 'allow' } );
	} );

	it( 'allows a category of the API when any accepted category maps to it', () => {
		const mapping = {
			statistics: [ 'statistics' ],
			social: [ 'marketing' ],
			marketing: [ 'marketing' ],
		};

		expect( consentValues( mapping, [ 'social' ] ) ).toEqual( {
			statistics: 'deny',
			marketing: 'allow',
		} );
	} );
} );

describe( 'parseWpConsentApiConfig', () => {
	it( 'reads the consent type and the mapping', () => {
		expect( parseWpConsentApiConfig( config ) ).toEqual( config );
	} );

	it( 'falls back to opt-in and an empty mapping', () => {
		expect( parseWpConsentApiConfig( null ) ).toEqual( {
			consentType: 'optin',
			mapping: {},
		} );
	} );
} );

describe( 'startWpConsentApi', () => {
	it( 'defines the consent type and dispatches its event', () => {
		const listener = jest.fn();
		document.addEventListener( CONSENT_TYPE_DEFINED, listener );

		start();

		expect( window.wp_consent_type ).toBe( 'optin' );
		expect( listener ).toHaveBeenCalledTimes( 1 );
		document.removeEventListener( CONSENT_TYPE_DEFINED, listener );
	} );

	it( 'denies every category but the required ones without a consent', () => {
		expect( values( start() ) ).toEqual( DENIED );
	} );

	it( 'writes the stored consent', () => {
		const api = consentApi();
		api.setConsent( [ 'statistics' ] );

		expect( values( start( api ) ) ).toEqual( {
			...DENIED,
			statistics: 'allow',
			'statistics-anonymous': 'allow',
		} );
	} );

	it( 'writes every choice of the visitor', () => {
		const api = consentApi();
		const setConsent = start( api );
		setConsent.mockClear();

		api.acceptAll();

		expect( values( setConsent ) ).toEqual( {
			functional: 'allow',
			statistics: 'allow',
			'statistics-anonymous': 'allow',
			marketing: 'allow',
		} );
	} );

	it( 'denies the categories when the consent is withdrawn', () => {
		const api = consentApi();
		api.acceptAll();
		const setConsent = start( api );
		setConsent.mockClear();

		api.withdraw();

		expect( values( setConsent ) ).toEqual( DENIED );
	} );
} );
