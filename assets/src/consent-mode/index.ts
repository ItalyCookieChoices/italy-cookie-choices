/**
 * Italy Cookie Choices Google Consent Mode v2, printed inline at the start of the head
 * by ItalyCookieChoices\Integrations\UI\ConsentModeScript.
 */
import { parseConfig } from '../consent/config';
import { Cookies } from '../consent/cookies';
import { parseMapping, startConsentMode, type Gtag } from './consent-mode';

declare global {
	interface Window {
		italyCookieChoicesConsentMode?: unknown;
		dataLayer?: unknown[];
		gtag?: Gtag;
	}
}

const config = window.italyCookieChoicesConsentMode;
if ( typeof config === 'object' && config !== null ) {
	const raw = config as Record< string, unknown >;
	const dataLayer = window.dataLayer ?? [];
	window.dataLayer = dataLayer;
	// The Google tags read the arguments object, not an array.
	window.gtag =
		window.gtag ??
		function gtag() {
			// eslint-disable-next-line prefer-rest-params
			dataLayer.push( arguments );
		};

	startConsentMode(
		{
			consent: parseConfig( raw.consent ),
			mapping: parseMapping( raw.mapping ),
			waitForUpdate:
				typeof raw.waitForUpdate === 'number' ? raw.waitForUpdate : 500,
		},
		window.gtag,
		new Cookies( document, window.location.protocol === 'https:' ),
		document
	);
}
