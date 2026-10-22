/**
 * Italy Cookie Choices consent log, it needs the consent API of assets/src/consent.
 *
 * The URL is printed by ItalyCookieChoices\ConsentLog\UI\LogScript.
 */
import { logConsentChanges, sendWithFetch } from './sender';

declare global {
	interface Window {
		italyCookieChoicesLog?: unknown;
	}
}

const config = window.italyCookieChoicesLog;
if (
	typeof config === 'object' &&
	config !== null &&
	'url' in config &&
	typeof config.url === 'string' &&
	config.url !== ''
) {
	logConsentChanges( document, config.url, sendWithFetch );
}
