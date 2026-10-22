import { listenToConsentButtons } from '../actions';
import { createConsentApi } from '../api';
import { Cookies } from '../cookies';
import type { ConsentApi, ConsentConfig } from '../types';

const config: ConsentConfig = {
	cookieName: 'icc_consent',
	categories: [
		{ id: 'necessary', required: true },
		{ id: 'statistics', required: false },
		{ id: 'marketing', required: false },
	],
	version: '1',
	durationDays: 182,
	legacyCookie: null,
};

function clearCookies(): void {
	document.cookie.split( '; ' ).forEach( ( cookie ) => {
		const name = cookie.split( '=' )[ 0 ];
		document.cookie = `${ name }=; expires=${ new Date(
			0
		).toUTCString() }; path=/`;
	} );
}

function click( selector: string ): void {
	const button = document.querySelector< HTMLElement >( selector );
	if ( button === null ) {
		throw new Error( `Missing ${ selector }` );
	}

	button.click();
}

describe( 'listenToConsentButtons', () => {
	let api: ConsentApi;

	beforeAll( () => {
		api = createConsentApi(
			config,
			new Cookies( document, false ),
			new EventTarget()
		);
		listenToConsentButtons( document, api );
	} );

	beforeEach( () => {
		clearCookies();
		document.body.innerHTML = `
			<button id="marketing" data-icc-accept="marketing"><span>Show the video</span></button>
			<button id="statistics" data-icc-accept="statistics">Statistics</button>
			<button id="all" data-icc-accept-all>Accept all</button>
			<button id="other">Other</button>
		`;
	} );

	it( 'accepts the category of the button', () => {
		click( '#marketing span' );

		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'marketing',
		] );
	} );

	it( 'keeps the categories already accepted', () => {
		click( '#statistics' );
		click( '#marketing' );

		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'statistics',
			'marketing',
		] );
	} );

	it( 'accepts every category', () => {
		click( '#all' );

		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'statistics',
			'marketing',
		] );
	} );

	it( 'ignores the other clicks', () => {
		click( '#other' );

		expect( api.getConsent() ).toBeNull();
	} );
} );
