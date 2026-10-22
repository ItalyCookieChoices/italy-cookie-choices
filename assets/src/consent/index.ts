/**
 * Italy Cookie Choices consent API.
 *
 * window.italyCookieChoices.hasConsent( 'statistics' );
 * document.addEventListener( 'italy-cookie-choices:consent-changed', ( event ) => event.detail.consent );
 * <button data-icc-accept="marketing">Show the video</button>
 *
 * The consent is read in the browser, so the pages can be cached.
 */
import { listenToConsentButtons } from './actions';
import { createConsentApi } from './api';
import { parseConfig } from './config';
import { Cookies } from './cookies';
import { syncLegacyCookie } from './legacy-cookie';
import { CHANNEL, syncTabs } from './tab-sync';
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
	syncTabs(
		api,
		document,
		typeof BroadcastChannel === 'function'
			? new BroadcastChannel( CHANNEL )
			: null
	);

	syncLegacyCookie( api, cookies, config );
	listenToConsentButtons( document, api );

	const ready = (): void => {
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
