import { createConsentApi } from '../../consent/api';
import { Cookies } from '../../consent/cookies';
import type { ConsentApi, ConsentConfig } from '../../consent/types';
import {
	clarityQueue,
	parseMicrosoftConsentConfig,
	startClarityConsent,
	startUetConsent,
} from '../microsoft-consent';

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

const UET = { marketing: [ 'ad_storage' ] };
const CLARITY = {
	statistics: [ 'analytics_Storage' ],
	marketing: [ 'ad_Storage' ],
};

function consentApi(): ConsentApi {
	return createConsentApi(
		consentConfig,
		new Cookies( document, false ),
		document
	);
}

afterEach( () => {
	document.cookie = `icc_consent=; expires=${ new Date( 0 ).toUTCString() }; path=/`;
} );

describe( 'parseMicrosoftConsentConfig', () => {
	it( 'reads the mappings of the signals turned on', () => {
		const config = parseMicrosoftConsentConfig( {
			consent: consentConfig,
			uet: UET,
			clarity: null,
		} );

		expect( config.uet ).toEqual( UET );
		expect( config.clarity ).toBeNull();
		expect( config.consent.cookieName ).toBe( 'icc_consent' );
	} );

	it( 'turns every signal off without a configuration', () => {
		const config = parseMicrosoftConsentConfig( null );

		expect( config.uet ).toBeNull();
		expect( config.clarity ).toBeNull();
	} );
} );

describe( 'startUetConsent', () => {
	it( 'denies the ad storage by default', () => {
		const uetq: unknown[][] = [];

		startUetConsent( UET, consentApi(), {
			push: ( ...args: unknown[] ) => uetq.push( args ),
		} );

		expect( uetq ).toEqual( [
			[ 'consent', 'default', { ad_storage: 'denied' } ],
		] );
	} );

	it( 'updates the default with the stored consent', () => {
		const api = consentApi();
		api.setConsent( [ 'marketing' ] );
		const uetq: unknown[][] = [];

		startUetConsent( UET, api, {
			push: ( ...args: unknown[] ) => uetq.push( args ),
		} );

		expect( uetq[ 1 ] ).toEqual( [
			'consent',
			'update',
			{ ad_storage: 'granted' },
		] );
	} );

	it( 'updates the consent on every choice and on a withdrawal', () => {
		const api = consentApi();
		const uetq: unknown[][] = [];
		startUetConsent( UET, api, {
			push: ( ...args: unknown[] ) => uetq.push( args ),
		} );

		api.acceptAll();
		api.withdraw();

		expect( uetq.slice( 1 ) ).toEqual( [
			[ 'consent', 'update', { ad_storage: 'granted' } ],
			[ 'consent', 'update', { ad_storage: 'denied' } ],
		] );
	} );
} );

describe( 'startClarityConsent', () => {
	it( 'denies both storages before the choice', () => {
		const clarity = jest.fn();

		startClarityConsent( CLARITY, consentApi(), clarity );

		expect( clarity.mock.calls ).toEqual( [
			[
				'consentv2',
				{ analytics_Storage: 'denied', ad_Storage: 'denied' },
			],
		] );
	} );

	it( 'sends the stored consent and every choice', () => {
		const api = consentApi();
		api.setConsent( [ 'statistics' ] );
		const clarity = jest.fn();

		startClarityConsent( CLARITY, api, clarity );
		api.acceptAll();

		expect( clarity.mock.calls ).toEqual( [
			[
				'consentv2',
				{ analytics_Storage: 'granted', ad_Storage: 'denied' },
			],
			[
				'consentv2',
				{ analytics_Storage: 'granted', ad_Storage: 'granted' },
			],
		] );
	} );
} );

describe( 'clarityQueue', () => {
	it( 'queues the calls until Clarity loads, as its snippet does', () => {
		const clarity = clarityQueue();

		clarity( 'consentv2', { ad_Storage: 'denied' } );

		expect(
			clarity.q?.map( ( args ) =>
				Array.from( args as ArrayLike< unknown > )
			)
		).toEqual( [ [ 'consentv2', { ad_Storage: 'denied' } ] ] );
	} );
} );
