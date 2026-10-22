import type { Cookies } from './cookies';
import type { Consent, ConsentApi, ConsentConfig } from './types';

type AllowCookie = ( ( ...args: unknown[] ) => unknown ) & {
	italyCookieChoicesBridge?: true;
};

declare global {
	interface Window {
		/** Defined by the 2.x script, it restores the blocked content in the page. */
		allowCookie?: AllowCookie;
	}
}

const DAY = 24 * 60 * 60 * 1000;

/**
 * Keeps the 2.x blocking working until the new blocking replaces it.
 *
 * The 2.x blocking is not split by category: the blocked content needs the consent to every category.
 * The 2.x cookie exists only while that consent lasts, the server reads it to stop blocking.
 * A visitor who accepted with the 2.x banner is asked again, that consent may come from a scroll.
 *
 * @param api     The consent API.
 * @param cookies The site cookies.
 * @param config  The consent configuration.
 * @param window  The window where the 2.x script defines allowCookie().
 */
export function bridgeLegacyBlocking(
	api: ConsentApi,
	cookies: Cookies,
	config: ConsentConfig,
	window: Pick< Window, 'allowCookie' >
): void {
	const legacyCookie = config.legacyCookie;
	if ( legacyCookie === null ) {
		return;
	}

	const optional = config.categories
		.filter( ( category ) => ! category.required )
		.map( ( category ) => category.id );
	const acceptsEverything = ( consent: Consent | null ): boolean =>
		consent !== null &&
		optional.every( ( category ) =>
			consent.categories.includes( category )
		);

	const syncCookie = ( consent: Consent | null ): void => {
		if ( consent === null || ! acceptsEverything( consent ) ) {
			cookies.remove( legacyCookie.name );
			return;
		}

		const expires = new Date(
			Date.parse( consent.date ) + config.durationDays * DAY
		);
		cookies.write( legacyCookie.name, legacyCookie.value, expires );
	};

	const restore = window.allowCookie;
	syncCookie( api.getConsent() );

	api.onChange( ( { consent, previous } ) => {
		syncCookie( consent );
		if (
			typeof restore === 'function' &&
			acceptsEverything( consent ) &&
			! acceptsEverything( previous )
		) {
			restore();
		}
	} );

	if (
		typeof restore !== 'function' ||
		restore.italyCookieChoicesBridge === true
	) {
		return;
	}

	// The buttons in the blocked content accept everything, the change listener restores the content.
	const bridged: AllowCookie = () => {
		if ( acceptsEverything( api.getConsent() ) ) {
			return restore();
		}

		api.acceptAll();
		return undefined;
	};
	bridged.italyCookieChoicesBridge = true;
	window.allowCookie = bridged;
}
