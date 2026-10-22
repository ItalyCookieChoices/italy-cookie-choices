import { createConsentApi } from '../api';
import { Cookies } from '../cookies';
import { bridgeLegacyBanner } from '../legacy-bridge';
import type { ConsentConfig } from '../types';

const legacyCookie = { name: 'displayCookieConsent', value: 'y' };

const config: ConsentConfig = {
	cookieName: 'icc_consent',
	categories: [
		{ id: 'necessary', required: true },
		{ id: 'statistics', required: false },
	],
	version: '1',
	durationDays: 182,
	legacyCookie,
};

function clearCookies(): void {
	document.cookie.split( '; ' ).forEach( ( cookie ) => {
		const name = cookie.split( '=' )[ 0 ];
		document.cookie = `${ name }=; expires=${ new Date( 0 ).toUTCString() }; path=/`;
	} );
}

function setUp() {
	const cookies = new Cookies( document, false );
	const api = createConsentApi( config, cookies, document );

	return { api, cookies };
}

describe( 'bridgeLegacyBanner', () => {
	beforeEach( clearCookies );

	it( 'gives the consent to all categories when the 2.x banner accepts', () => {
		const { api, cookies } = setUp();
		const allowCookie = jest.fn( () => 'allowed' );
		const legacyWindow: Pick< Window, 'allowCookie' > = { allowCookie };

		bridgeLegacyBanner(
			api,
			cookies,
			'icc_consent',
			legacyCookie,
			legacyWindow
		);

		expect( legacyWindow.allowCookie?.( 'argument' ) ).toBe( 'allowed' );
		expect( allowCookie ).toHaveBeenCalledWith( 'argument' );
		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'statistics',
		] );
	} );

	it( 'wraps the 2.x accept only once', () => {
		const { api, cookies } = setUp();
		const legacyWindow: Pick< Window, 'allowCookie' > = {
			allowCookie: jest.fn(),
		};

		bridgeLegacyBanner(
			api,
			cookies,
			'icc_consent',
			legacyCookie,
			legacyWindow
		);
		const bridged = legacyWindow.allowCookie;
		bridgeLegacyBanner(
			api,
			cookies,
			'icc_consent',
			legacyCookie,
			legacyWindow
		);

		expect( legacyWindow.allowCookie ).toBe( bridged );
	} );

	it( 'does nothing without the 2.x banner', () => {
		const { api, cookies } = setUp();
		const legacyWindow: Pick< Window, 'allowCookie' > = {};

		bridgeLegacyBanner(
			api,
			cookies,
			'icc_consent',
			legacyCookie,
			legacyWindow
		);

		expect( legacyWindow.allowCookie ).toBeUndefined();
		expect( api.getConsent() ).toBeNull();
	} );

	it( 'converts the consent given with the 2.x banner', () => {
		const { api, cookies } = setUp();
		document.cookie = 'displayCookieConsent=y; path=/';

		bridgeLegacyBanner( api, cookies, 'icc_consent', legacyCookie, {} );

		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'statistics',
		] );
	} );

	it( 'keeps an existing consent', () => {
		const { api, cookies } = setUp();
		const consent = api.rejectAll();
		document.cookie = 'displayCookieConsent=y; path=/';

		bridgeLegacyBanner( api, cookies, 'icc_consent', legacyCookie, {} );

		expect( api.getConsent() ).toEqual( consent );
	} );

	it( 'ignores a 2.x cookie with another value', () => {
		const { api, cookies } = setUp();
		document.cookie = 'displayCookieConsent=n; path=/';

		bridgeLegacyBanner( api, cookies, 'icc_consent', legacyCookie, {} );

		expect( api.getConsent() ).toBeNull();
	} );
} );
