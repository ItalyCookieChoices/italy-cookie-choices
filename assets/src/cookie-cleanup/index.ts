/**
 * Italy Cookie Choices deletes the cookies of the categories without the consent, loaded by
 * ItalyCookieChoices\Cookies\UI\CookieCleanupScript after the consent API.
 */
import { parseCookieCleanupConfig, startCookieCleanup } from './cookie-cleanup';

declare global {
	interface Window {
		italyCookieChoicesCookieCleanup?: unknown;
	}
}

const consent = window.italyCookieChoices;
if ( consent !== undefined ) {
	startCookieCleanup(
		parseCookieCleanupConfig( window.italyCookieChoicesCookieCleanup ),
		consent,
		document,
		window.location.hostname
	);
}
