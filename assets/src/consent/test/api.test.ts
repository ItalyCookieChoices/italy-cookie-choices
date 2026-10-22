import { createConsentApi } from '../api';
import { Cookies } from '../cookies';
import {
	CONSENT_CHANGED,
	type ConsentChange,
	type ConsentConfig,
} from '../types';

const NOW = new Date( '2026-10-22T10:00:00.000Z' );
const DAY = 24 * 60 * 60 * 1000;

const config: ConsentConfig = {
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

function clearCookies(): void {
	document.cookie.split( '; ' ).forEach( ( cookie ) => {
		const name = cookie.split( '=' )[ 0 ];
		document.cookie = `${ name }=; expires=${ new Date( 0 ).toUTCString() }; path=/`;
	} );
}

function storeConsent( consent: object ): void {
	document.cookie = `icc_consent=${ encodeURIComponent( JSON.stringify( consent ) ) }; path=/`;
}

function makeApi( now: Date = NOW ) {
	return createConsentApi(
		config,
		new Cookies( document, false ),
		document,
		() => now
	);
}

function recordChanges(): ConsentChange[] {
	const changes: ConsentChange[] = [];
	document.addEventListener( CONSENT_CHANGED, ( event ) => {
		changes.push( ( event as CustomEvent< ConsentChange > ).detail );
	} );

	return changes;
}

describe( 'consent API', () => {
	beforeEach( clearCookies );

	it( 'has no consent before the visitor chooses', () => {
		const api = makeApi();

		expect( api.getConsent() ).toBeNull();
		expect( api.hasConsent( 'statistics' ) ).toBe( false );
	} );

	it( 'always allows the required categories', () => {
		expect( makeApi().hasConsent( 'necessary' ) ).toBe( true );
	} );

	it( 'lists the categories', () => {
		expect( makeApi().categories() ).toEqual( config.categories );
	} );

	it( 'saves the accepted categories with the required ones, in the configured order', () => {
		const api = makeApi();

		const consent = api.setConsent( [ 'marketing', 'unknown' ] );

		expect( consent ).toEqual( {
			categories: [ 'necessary', 'marketing' ],
			version: '1',
			date: NOW.toISOString(),
		} );
		expect( api.getConsent() ).toEqual( consent );
		expect( api.hasConsent( 'marketing' ) ).toBe( true );
		expect( api.hasConsent( 'statistics' ) ).toBe( false );
	} );

	it( 'accepts every category', () => {
		expect( makeApi().acceptAll().categories ).toEqual( [
			'necessary',
			'statistics',
			'marketing',
		] );
	} );

	it( 'rejects every optional category', () => {
		expect( makeApi().rejectAll().categories ).toEqual( [ 'necessary' ] );
	} );

	it( 'writes the cookie for the configured duration', () => {
		const write = jest.spyOn( Cookies.prototype, 'write' );

		makeApi().acceptAll();

		expect( write ).toHaveBeenCalledWith(
			'icc_consent',
			expect.any( String ),
			new Date( NOW.getTime() + 10 * DAY )
		);
		write.mockRestore();
	} );

	it( 'ignores a consent given for another policy version', () => {
		storeConsent( {
			categories: [ 'necessary', 'statistics' ],
			version: '0',
			date: NOW.toISOString(),
		} );

		expect( makeApi().getConsent() ).toBeNull();
	} );

	it( 'ignores an expired consent', () => {
		makeApi().acceptAll();

		expect(
			makeApi( new Date( NOW.getTime() + 10 * DAY ) ).getConsent()
		).toBeNull();
		expect(
			makeApi( new Date( NOW.getTime() + 10 * DAY - 1 ) ).getConsent()
		).not.toBeNull();
	} );

	it.each( [
		[ 'not JSON', 'not-json' ],
		[
			'without categories',
			encodeURIComponent( '{"version":"1","date":"x"}' ),
		],
		[
			'with a category that is not a string',
			encodeURIComponent(
				'{"categories":[1],"version":"1","date":"2026-10-22T10:00:00.000Z"}'
			),
		],
		[
			'with an invalid date',
			encodeURIComponent( '{"categories":[],"version":"1","date":"x"}' ),
		],
	] )( 'ignores a cookie %s', ( _name, value ) => {
		document.cookie = `icc_consent=${ value }; path=/`;

		expect( makeApi().getConsent() ).toBeNull();
	} );

	it( 'dispatches the change with the previous consent', () => {
		const changes = recordChanges();
		const api = makeApi();

		const first = api.rejectAll();
		const second = api.acceptAll();

		expect( changes.slice( -2 ) ).toEqual( [
			{ consent: first, previous: null },
			{ consent: second, previous: first },
		] );
	} );

	it( 'withdraws the consent', () => {
		const changes = recordChanges();
		const api = makeApi();
		const consent = api.acceptAll();

		api.withdraw();

		expect( api.getConsent() ).toBeNull();
		expect( document.cookie ).not.toContain( 'icc_consent' );
		expect( changes[ changes.length - 1 ] ).toEqual( {
			consent: null,
			previous: consent,
		} );
	} );

	it( 'calls the listeners until they are removed', () => {
		const listener = jest.fn();
		const api = makeApi();
		const remove = api.onChange( listener );

		const consent = api.acceptAll();
		remove();
		api.rejectAll();

		expect( listener ).toHaveBeenCalledTimes( 1 );
		expect( listener ).toHaveBeenCalledWith( consent, null );
	} );

	it( 'returns copies of the consent', () => {
		const api = makeApi();
		api.acceptAll();

		api.getConsent()?.categories.push( 'other' );

		expect( api.getConsent()?.categories ).not.toContain( 'other' );
	} );
} );
