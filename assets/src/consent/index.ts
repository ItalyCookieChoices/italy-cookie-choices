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
import { renewConsentCookie, renewWithFetch } from './renewal';
import { createScanApi } from './scan';
import { CHANNEL, syncTabs } from './tab-sync';
import { READY, type ConsentApi, type ConsentConfig } from './types';

declare global {
	interface Window {
		italyCookieChoicesConfig?: unknown;
		italyCookieChoices?: ConsentApi;
	}
}

/**
 * The consent API of the visitors, kept in the consent cookie and in sync with the other tabs.
 * @param config The consent configuration.
 */
function createSiteApi( config: ConsentConfig ): ConsentApi {
	const cookies = new Cookies(
		document,
		window.location.protocol === 'https:',
		config.cookieDomain
	);
	const api = createConsentApi( config, cookies, document );
	syncTabs(
		api,
		document,
		typeof BroadcastChannel === 'function'
			? new BroadcastChannel( CHANNEL )
			: null
	);

	syncLegacyCookie( api, cookies, config );
	if ( config.renewalUrl !== undefined ) {
		renewConsentCookie( document, config.renewalUrl, renewWithFetch, () =>
			cookies.remove( config.cookieName )
		);
	}

	return api;
}

if ( window.italyCookieChoices === undefined ) {
	const config = parseConfig( window.italyCookieChoicesConfig );
	const api =
		config.scan === true
			? createScanApi( config, document )
			: createSiteApi( config );
	window.italyCookieChoices = api;

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
