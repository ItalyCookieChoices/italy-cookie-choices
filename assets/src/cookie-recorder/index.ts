/**
 * Italy Cookie Choices cookie recorder, printed first in the head of a page in scan mode
 * by ItalyCookieChoices\Scan\UI\CookieRecorder: it records the cookies the scripts write,
 * the settings page reads them from the frame of the page.
 */
import { RECORDED_COOKIES, type RecordedCookie } from './recorded';
import { recordCookies } from './recorder';

declare global {
	interface Window {
		[ RECORDED_COOKIES ]?: RecordedCookie[];
	}
}

if ( window[ RECORDED_COOKIES ] === undefined ) {
	const recorded: RecordedCookie[] = [];
	window[ RECORDED_COOKIES ] = recorded;
	// Missing in the browsers without the Cookie Store API.
	recordCookies(
		document,
		'cookieStore' in window ? window.cookieStore : undefined,
		recorded
	);
}
