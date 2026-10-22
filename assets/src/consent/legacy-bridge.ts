import type { Cookies } from './cookies';
import type { ConsentApi, LegacyCookie } from './types';

type AllowCookie = ( ( ...args: unknown[] ) => unknown ) & {
	italyCookieChoicesBridge?: true;
};

declare global {
	interface Window {
		/** Defined by the 2.x banner script, it runs when the visitor accepts. */
		allowCookie?: AllowCookie;
	}
}

/**
 * The 2.x banner accepts every cookie: until the new banner replaces it, its accept gives the consent to all the categories.
 *
 * A visitor who accepted with the 2.x banner gets the same consent, unless a consent cookie already exists.
 * @param api               The consent API.
 * @param cookies           The site cookies.
 * @param consentCookieName The cookie of the consent API.
 * @param legacyCookie      The cookie written by the 2.x banner, null when unknown.
 * @param window            The window where the 2.x banner defines allowCookie().
 */
export function bridgeLegacyBanner(
	api: ConsentApi,
	cookies: Cookies,
	consentCookieName: string,
	legacyCookie: LegacyCookie | null,
	window: Pick< Window, 'allowCookie' >
): void {
	if (
		legacyCookie !== null &&
		cookies.read( consentCookieName ) === null &&
		cookies.read( legacyCookie.name ) === legacyCookie.value
	) {
		api.acceptAll();
	}

	const allowCookie = window.allowCookie;
	if (
		typeof allowCookie !== 'function' ||
		allowCookie.italyCookieChoicesBridge === true
	) {
		return;
	}

	const bridged: AllowCookie = function (
		this: unknown,
		...args: unknown[]
	) {
		const result = allowCookie.apply( this, args );
		api.acceptAll();

		return result;
	};
	bridged.italyCookieChoicesBridge = true;
	window.allowCookie = bridged;
}
