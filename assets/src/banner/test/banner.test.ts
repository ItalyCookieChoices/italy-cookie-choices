import { createConsentApi } from '../../consent/api';
import { Cookies } from '../../consent/cookies';
import {
	CONSENT_CHANGED,
	type ConsentApi,
	type ConsentChange,
	type ConsentConfig,
} from '../../consent/types';
import { Banner, OPEN_PREFERENCES } from '../banner';

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

const MARKUP = `
<div id="icc-banner" role="dialog" aria-modal="false" tabindex="-1" data-reload="0" data-html-margin="0" hidden>
	<p>Text <a href="/policy">Policy</a></p>
	<button type="button" data-icc-action="accept-all">Accept all</button>
	<button type="button" data-icc-action="reject-all">Reject all</button>
	<button type="button" data-icc-action="preferences" aria-expanded="false">Preferences</button>
	<button type="button" data-icc-action="close">Close</button>
</div>
<div id="icc-preferences" role="dialog" aria-modal="true" tabindex="-1" hidden>
	<form>
		<input type="checkbox" name="icc-category" value="necessary" checked disabled>
		<input type="checkbox" name="icc-category" value="statistics">
		<input type="checkbox" name="icc-category" value="marketing">
		<button type="submit">Save</button>
		<button type="button" data-icc-action="accept-all">Accept all</button>
	</form>
	<button type="button" data-icc-action="close-preferences">Close</button>
</div>
<a href="#icc-preferences" id="reopen">Cookie preferences</a>
`;

function clearCookies(): void {
	document.cookie.split( '; ' ).forEach( ( cookie ) => {
		const name = cookie.split( '=' )[ 0 ];
		document.cookie = `${ name }=; expires=${ new Date(
			0
		).toUTCString() }; path=/`;
	} );
}

function element< T extends HTMLElement = HTMLElement >( selector: string ): T {
	const found = document.querySelector< T >( selector );
	if ( found === null ) {
		throw new Error( `Missing ${ selector }` );
	}

	return found;
}

function setUp( dataset: Record< string, string > = {} ) {
	document.body.innerHTML = MARKUP;
	const banner = element( '#icc-banner' );
	Object.assign( banner.dataset, dataset );
	const preferences = element( '#icc-preferences' );
	const events = new EventTarget();
	const api: ConsentApi = createConsentApi(
		config,
		new Cookies( document, false ),
		events
	);
	const reload = jest.fn();

	return {
		api,
		events,
		banner,
		preferences,
		reload,
		start: () => new Banner( { banner, preferences }, api, reload ).start(),
	};
}

function press( target: HTMLElement, key: string, shiftKey = false ): void {
	target.dispatchEvent(
		new KeyboardEvent( 'keydown', { key, shiftKey, bubbles: true } )
	);
}

describe( 'Banner', () => {
	beforeEach( clearCookies );

	it( 'opens and takes the focus when there is no consent', () => {
		const { banner, start } = setUp();

		start();

		expect( banner.hidden ).toBe( false );
		expect( banner.ownerDocument.activeElement ).toBe( banner );
	} );

	it( 'stays closed where it does not open by itself', () => {
		const { banner, preferences, start } = setUp( { opensByItself: '0' } );

		start();
		expect( banner.hidden ).toBe( true );

		element( '#reopen' ).click();
		expect( preferences.hidden ).toBe( false );
	} );

	it( 'stays closed when the consent is given', () => {
		const { api, banner, start } = setUp();
		api.rejectAll();

		start();

		expect( banner.hidden ).toBe( true );
	} );

	it.each( [
		[ 'accept-all', [ 'necessary', 'statistics', 'marketing' ] ],
		[ 'reject-all', [ 'necessary' ] ],
		[ 'close', [ 'necessary' ] ],
	] )( 'saves the consent with %s and closes', ( action, categories ) => {
		const { api, banner, start } = setUp();
		start();

		element( `#icc-banner [data-icc-action="${ action }"]` ).click();

		expect( api.getConsent()?.categories ).toEqual( categories );
		expect( banner.hidden ).toBe( true );
	} );

	it( 'keeps only the necessary cookies on Escape', () => {
		const { api, banner, start } = setUp();
		start();

		press( banner, 'Escape' );

		expect( api.getConsent()?.categories ).toEqual( [ 'necessary' ] );
	} );

	it( 'opens the preferences with the focus on the first choice', () => {
		const { banner, preferences, start } = setUp();
		start();
		const trigger = element( '[data-icc-action="preferences"]' );

		trigger.click();

		expect( preferences.hidden ).toBe( false );
		expect( trigger.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( banner.ownerDocument.activeElement ).toBe(
			element( 'input[value="statistics"]' )
		);
		expect( banner.hidden ).toBe( false );
	} );

	it( 'saves the chosen categories', () => {
		const { api, banner, preferences, start } = setUp();
		start();
		element( '[data-icc-action="preferences"]' ).click();

		element< HTMLInputElement >( 'input[value="marketing"]' ).checked =
			true;
		element< HTMLFormElement >( '#icc-preferences form' ).dispatchEvent(
			new Event( 'submit', { bubbles: true, cancelable: true } )
		);

		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'marketing',
		] );
		expect( preferences.hidden ).toBe( true );
		expect( banner.hidden ).toBe( true );
	} );

	it( 'shows the current choices when the preferences open again', () => {
		const { api, start } = setUp();
		api.setConsent( [ 'statistics' ] );
		start();

		element( '#reopen' ).click();

		expect(
			element< HTMLInputElement >( 'input[value="statistics"]' ).checked
		).toBe( true );
		expect(
			element< HTMLInputElement >( 'input[value="marketing"]' ).checked
		).toBe( false );
	} );

	it( 'closes the preferences on Escape and gives the focus back', () => {
		const { preferences, banner, start } = setUp();
		start();
		const trigger = element( '[data-icc-action="preferences"]' );
		trigger.click();

		press( element( 'input[value="statistics"]' ), 'Escape' );

		expect( preferences.hidden ).toBe( true );
		expect( banner.hidden ).toBe( false );
		expect( trigger.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( banner.ownerDocument.activeElement ).toBe( trigger );
	} );

	it( 'closes the preferences with the close button', () => {
		const { preferences, start } = setUp();
		start();
		element( '[data-icc-action="preferences"]' ).click();

		element( '[data-icc-action="close-preferences"]' ).click();

		expect( preferences.hidden ).toBe( true );
	} );

	it( 'keeps the Tab key inside the preferences', () => {
		const { banner, start } = setUp();
		start();
		element( '[data-icc-action="preferences"]' ).click();
		const first = element( 'input[value="statistics"]' );
		const last = element( '[data-icc-action="close-preferences"]' );

		last.focus();
		press( last, 'Tab' );
		expect( banner.ownerDocument.activeElement ).toBe( first );

		press( first, 'Tab', true );
		expect( banner.ownerDocument.activeElement ).toBe( last );
	} );

	it( 'opens the preferences from the event', () => {
		const { preferences, start } = setUp();
		start();

		document.dispatchEvent( new CustomEvent( OPEN_PREFERENCES ) );

		expect( preferences.hidden ).toBe( false );
	} );

	it( 'reloads the page after a choice when the option is on', () => {
		const { reload, start } = setUp( { reload: '1' } );
		start();

		element( '[data-icc-action="accept-all"]' ).click();

		expect( reload ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not reload when the option is off', () => {
		const { reload, start } = setUp();
		start();

		element( '[data-icc-action="accept-all"]' ).click();

		expect( reload ).not.toHaveBeenCalled();
	} );

	it( 'closes without reload when another tab gives the consent', () => {
		const { banner, events, reload, start } = setUp( { reload: '1' } );
		start();

		events.dispatchEvent(
			new CustomEvent< ConsentChange >( CONSENT_CHANGED, {
				detail: {
					consent: {
						id: '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
						categories: [ 'necessary' ],
						version: '1',
						date: new Date().toISOString(),
					},
					previous: null,
					fromAnotherTab: true,
				},
			} )
		);

		expect( banner.hidden ).toBe( true );
		expect( reload ).not.toHaveBeenCalled();
	} );

	it( 'opens again when the consent is withdrawn', () => {
		const { api, banner, start } = setUp();
		api.rejectAll();
		start();

		api.withdraw();

		expect( banner.hidden ).toBe( false );
	} );

	it( 'pushes the page down for the top bar when the option is on', () => {
		const { api, start } = setUp( { htmlMargin: '1' } );
		start();

		expect(
			document.documentElement.classList.contains( 'icc-banner-is-open' )
		).toBe( true );

		api.acceptAll();

		expect(
			document.documentElement.classList.contains( 'icc-banner-is-open' )
		).toBe( false );
	} );
} );
