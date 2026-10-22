/**
 * Italy Cookie Choices consent API.
 *
 * window.italyCookieChoices.hasConsent( 'statistics' );
 * document.addEventListener( 'italy-cookie-choices:consent-changed', ( event ) => event.detail.consent );
 *
 * The consent is read in the browser, so the pages can be cached.
 */
import { createConsentApi } from './api';
import { parseConfig } from './config';
import { Cookies } from './cookies';
import { bridgeLegacyBanner } from './legacy-bridge';
import { READY, type ConsentApi } from './types';

declare global {
	interface Window {
		italyCookieChoicesConfig?: unknown;
		italyCookieChoices?: ConsentApi;
	}
}

if ( window.italyCookieChoices === undefined ) {
	const config = parseConfig( window.italyCookieChoicesConfig );
	const cookies = new Cookies(
		document,
		window.location.protocol === 'https:'
	);
	const api = createConsentApi( config, cookies, document );
	window.italyCookieChoices = api;

	const ready = (): void => {
		bridgeLegacyBanner(
			api,
			cookies,
			config.cookieName,
			config.legacyCookie,
			window
		);
		document.dispatchEvent(
			new CustomEvent( READY, { detail: { consent: api.getConsent() } } )
		);
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
}
