import { createConsentApi } from '../api';
import { Cookies } from '../cookies';
import { bridgeLegacyBlocking } from '../legacy-bridge';
import type { ConsentConfig } from '../types';

const config: ConsentConfig = {
	cookieName: 'icc_consent',
	categories: [
		{ id: 'necessary', required: true },
		{ id: 'statistics', required: false },
		{ id: 'marketing', required: false },
	],
	version: '1',
	durationDays: 182,
	legacyCookie: { name: 'displayCookieConsent', value: 'y' },
};

function clearCookies(): void {
	document.cookie.split( '; ' ).forEach( ( cookie ) => {
		const name = cookie.split( '=' )[ 0 ];
		document.cookie = `${ name }=; expires=${ new Date(
			0
		).toUTCString() }; path=/`;
	} );
}

function setUp( restore?: jest.Mock ) {
	const cookies = new Cookies( document, false );
	const api = createConsentApi( config, cookies, new EventTarget() );
	const legacyWindow: Pick< Window, 'allowCookie' > =
		restore === undefined ? {} : { allowCookie: restore };
	bridgeLegacyBlocking( api, cookies, config, legacyWindow );

	return { api, cookies, legacyWindow };
}

describe( 'bridgeLegacyBlocking', () => {
	beforeEach( clearCookies );

	it( 'writes the 2.x cookie when every category is accepted', () => {
		const { api, cookies } = setUp();

		api.acceptAll();

		expect( cookies.read( 'displayCookieConsent' ) ).toBe( 'y' );
	} );

	it( 'writes the 2.x cookie until the consent expires', () => {
		const { api } = setUp();
		const write = jest.spyOn( Cookies.prototype, 'write' );

		const consent = api.acceptAll();

		expect( write ).toHaveBeenCalledWith(
			'displayCookieConsent',
			'y',
			new Date( Date.parse( consent.date ) + 182 * 24 * 60 * 60 * 1000 )
		);
		write.mockRestore();
	} );

	it( 'removes the 2.x cookie when a category is refused', () => {
		const { api, cookies } = setUp();
		api.acceptAll();

		api.setConsent( [ 'statistics' ] );

		expect( cookies.read( 'displayCookieConsent' ) ).toBeNull();
	} );

	it( 'removes the 2.x cookie of a visitor without the new consent', () => {
		document.cookie = 'displayCookieConsent=y; path=/';

		const { api, cookies } = setUp();

		expect( cookies.read( 'displayCookieConsent' ) ).toBeNull();
		expect( api.getConsent() ).toBeNull();
	} );

	it( 'restores the blocked content when every category is accepted', () => {
		const restore = jest.fn();
		const { api } = setUp( restore );

		api.setConsent( [ 'statistics' ] );
		expect( restore ).not.toHaveBeenCalled();

		api.acceptAll();
		api.acceptAll();
		expect( restore ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'accepts every category from the buttons in the blocked content', () => {
		const restore = jest.fn();
		const { api, legacyWindow } = setUp( restore );

		legacyWindow.allowCookie?.();

		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'statistics',
			'marketing',
		] );
		expect( restore ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'restores directly when the consent is already given', () => {
		const restore = jest.fn( () => 'restored' );
		const { api, legacyWindow } = setUp( restore );
		api.acceptAll();

		expect( legacyWindow.allowCookie?.() ).toBe( 'restored' );
		expect( restore ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'wraps the 2.x function only once', () => {
		const restore = jest.fn();
		const { api, cookies, legacyWindow } = setUp( restore );
		const bridged = legacyWindow.allowCookie;

		bridgeLegacyBlocking( api, cookies, config, legacyWindow );

		expect( legacyWindow.allowCookie ).toBe( bridged );
	} );

	it( 'does nothing without the 2.x cookie', () => {
		const cookies = new Cookies( document, false );
		const withoutLegacy = { ...config, legacyCookie: null };
		const api = createConsentApi(
			withoutLegacy,
			cookies,
			new EventTarget()
		);
		const legacyWindow: Pick< Window, 'allowCookie' > = {
			allowCookie: jest.fn(),
		};
		const original = legacyWindow.allowCookie;

		bridgeLegacyBlocking( api, cookies, withoutLegacy, legacyWindow );
		api.acceptAll();

		expect( legacyWindow.allowCookie ).toBe( original );
		expect( cookies.read( 'displayCookieConsent' ) ).toBeNull();
	} );
} );
