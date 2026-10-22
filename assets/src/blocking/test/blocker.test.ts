import { createConsentApi } from '../../consent/api';
import { Cookies } from '../../consent/cookies';
import type { ConsentApi, ConsentConfig } from '../../consent/types';
import { Blocker } from '../blocker';

declare global {
	interface Window {
		iccRuns?: string[];
	}
}

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

const PLACEHOLDERS = `
<template data-icc-placeholder="statistics">
	<div class="icc-placeholder"><p>Statistics blocked</p><button data-icc-accept="statistics">Accept</button></div>
</template>
<template data-icc-placeholder="marketing">
	<div class="icc-placeholder">
		<p>Marketing blocked</p>
		<button data-icc-accept="marketing">Accept</button>
		<a href="#icc-preferences" class="icc-placeholder__preferences">Preferences</a>
	</div>
</template>
`;

const VIDEO =
	'<iframe id="video" width="560" height="315" data-category="marketing" data-icc-src="https://www.youtube.com/embed/a"></iframe>';

function clearCookies(): void {
	document.cookie.split( '; ' ).forEach( ( cookie ) => {
		const name = cookie.split( '=' )[ 0 ];
		document.cookie = `${ name }=; expires=${ new Date(
			0
		).toUTCString() }; path=/`;
	} );
}

function element< T extends Element = HTMLElement >( selector: string ): T {
	const found = document.querySelector< T >( selector );
	if ( found === null ) {
		throw new Error( `Missing ${ selector }` );
	}

	return found;
}

function setUp( markup: string ): { api: ConsentApi; blocker: Blocker } {
	document.body.innerHTML = markup + PLACEHOLDERS;
	const api = createConsentApi(
		config,
		new Cookies( document, false ),
		new EventTarget()
	);

	return { api, blocker: new Blocker( document, api ) };
}

function inlineScript( category: string, name: string ): string {
	return `<script type="text/plain" data-category="${ category }">window.iccRuns.push( '${ name }' );</script>`;
}

describe( 'Blocker', () => {
	beforeEach( () => {
		clearCookies();
		window.iccRuns = [];
	} );

	it( 'shows the placeholder of the category before a blocked frame', async () => {
		const { blocker } = setUp( VIDEO );

		await blocker.start();

		const placeholder = element( '.icc-placeholder' );
		expect( placeholder.nextElementSibling ).toBe( element( '#video' ) );
		expect( placeholder.textContent ).toContain( 'Marketing blocked' );
		expect( placeholder.style.aspectRatio ).toBe( '560 / 315' );
		expect( placeholder.style.maxWidth ).toBe( '560px' );
		expect( element( '#video' ).hasAttribute( 'data-icc-hidden' ) ).toBe(
			true
		);
		expect( element( '#video' ).hasAttribute( 'src' ) ).toBe( false );
	} );

	it( 'removes the preferences link where the dialog is missing', async () => {
		const { blocker } = setUp( VIDEO );

		await blocker.start();

		expect(
			document.querySelector( '.icc-placeholder__preferences' )
		).toBeNull();
	} );

	it( 'keeps the preferences link with the dialog', async () => {
		const { blocker } = setUp( VIDEO + '<div id="icc-preferences"></div>' );

		await blocker.start();

		expect(
			document.querySelector( '.icc-placeholder__preferences' )
		).not.toBeNull();
	} );

	it( 'loads the frame once its category is accepted', async () => {
		const { api, blocker } = setUp( VIDEO );
		await blocker.start();

		api.setConsent( [ 'marketing' ] );
		await blocker.update();

		const video = element( '#video' );
		expect( video.getAttribute( 'src' ) ).toBe(
			'https://www.youtube.com/embed/a'
		);
		expect( video.hasAttribute( 'data-icc-hidden' ) ).toBe( false );
		expect( document.querySelector( '.icc-placeholder' ) ).toBeNull();
	} );

	it( 'keeps the frame blocked when another category is accepted', async () => {
		const { api, blocker } = setUp( VIDEO );
		await blocker.start();

		api.setConsent( [ 'statistics' ] );
		await blocker.update();

		expect( element( '#video' ).hasAttribute( 'src' ) ).toBe( false );
		expect( document.querySelectorAll( '.icc-placeholder' ) ).toHaveLength(
			1
		);
	} );

	it( 'blocks the frame again when the consent is withdrawn', async () => {
		const { api, blocker } = setUp( VIDEO );
		api.acceptAll();
		await blocker.start();

		api.withdraw();
		await blocker.update();

		expect( element( '#video' ).hasAttribute( 'src' ) ).toBe( false );
		expect( element( '.icc-placeholder' ) ).toBeTruthy();
	} );

	it( 'leaves a frame without a placeholder empty', async () => {
		const { blocker } = setUp(
			'<iframe id="map" data-category="preferences" data-icc-src="https://example.com/map"></iframe>'
		);

		await blocker.start();

		expect( element( '#map' ).hasAttribute( 'data-icc-hidden' ) ).toBe(
			false
		);
		expect( element( '#map' ).hasAttribute( 'src' ) ).toBe( false );
	} );

	it( 'runs only the scripts of the accepted categories, in the page order', async () => {
		const { api, blocker } = setUp(
			inlineScript( 'statistics', 'first' ) +
				inlineScript( 'marketing', 'second' ) +
				inlineScript( 'statistics', 'third' )
		);
		await blocker.start();
		expect( window.iccRuns ).toEqual( [] );

		api.setConsent( [ 'statistics' ] );
		await blocker.update();

		expect( window.iccRuns ).toEqual( [ 'first', 'third' ] );
		expect(
			document.querySelectorAll( 'script[type="text/plain"]' )
		).toHaveLength( 1 );
	} );

	it( 'runs a script only once', async () => {
		const { api, blocker } = setUp( inlineScript( 'statistics', 'once' ) );
		api.acceptAll();

		await blocker.start();
		await blocker.update();

		expect( window.iccRuns ).toEqual( [ 'once' ] );
	} );

	it( 'runs the scripts when the consent changes', async () => {
		const { api, blocker } = setUp(
			inlineScript( 'marketing', 'changed' )
		);
		await blocker.start();

		api.acceptAll();
		await blocker.update();

		expect( window.iccRuns ).toEqual( [ 'changed' ] );
	} );

	it( 'keeps the attributes and restores the module type', async () => {
		const { api, blocker } = setUp(
			'<script id="module" type="text/plain" data-category="statistics" data-icc-type="module" data-site="a"></script>'
		);
		api.acceptAll();

		await blocker.start();

		const script = element< HTMLScriptElement >( '#module' );
		expect( script.type ).toBe( 'module' );
		expect( script.dataset.site ).toBe( 'a' );
		expect( script.hasAttribute( 'data-icc-type' ) ).toBe( false );
	} );

	it( 'waits for a script file before running the next script', async () => {
		const { api, blocker } = setUp(
			'<script id="library" type="text/plain" data-category="statistics" src="https://example.com/library.js"></script>' +
				inlineScript( 'statistics', 'after the library' )
		);
		api.acceptAll();

		const ran = blocker.start();
		await Promise.resolve();
		const library = element< HTMLScriptElement >( '#library' );
		expect( library.type ).toBe( '' );
		expect( library.async ).toBe( false );
		expect( window.iccRuns ).toEqual( [] );

		library.dispatchEvent( new Event( 'load' ) );
		await ran;

		expect( window.iccRuns ).toEqual( [ 'after the library' ] );
	} );

	it( 'does not wait for an async script file', async () => {
		const { api, blocker } = setUp(
			'<script id="async" type="text/plain" data-category="statistics" src="https://example.com/async.js" async></script>' +
				inlineScript( 'statistics', 'without waiting' )
		);
		api.acceptAll();

		await blocker.start();

		expect( element< HTMLScriptElement >( '#async' ).async ).toBe( true );
		expect( window.iccRuns ).toEqual( [ 'without waiting' ] );
	} );
} );
