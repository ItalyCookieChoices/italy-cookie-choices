import { createConsentApi } from '../api';
import { Cookies } from '../cookies';
import { syncLegacyCookie } from '../legacy-cookie';
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

function setUp() {
	const cookies = new Cookies( document, false );
	const api = createConsentApi( config, cookies, new EventTarget() );
	syncLegacyCookie( api, cookies, config );

	return { api, cookies };
}

describe( 'syncLegacyCookie', () => {
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

	it( 'does nothing without the 2.x cookie', () => {
		const cookies = new Cookies( document, false );
		const withoutLegacy = { ...config, legacyCookie: null };
		const api = createConsentApi(
			withoutLegacy,
			cookies,
			new EventTarget()
		);

		syncLegacyCookie( api, cookies, withoutLegacy );
		api.acceptAll();

		expect( cookies.read( 'displayCookieConsent' ) ).toBeNull();
	} );
} );
