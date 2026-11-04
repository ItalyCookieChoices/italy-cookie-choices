import { CONSENT_CHANGED, type ConsentChange } from './types';

export type Renew = ( url: string ) => Promise< void >;

function isChange( value: unknown ): value is ConsentChange {
	return typeof value === 'object' && value !== null && 'consent' in value;
}

/**
 * Asks the server to renew the consent cookie after each choice made in this tab.
 *
 * Safari keeps the cookies written by JavaScript for 7 days at most, the server writes the same cookie
 * again with its full duration. The renewals run one at a time, so the last choice is the last cookie,
 * and a renewal answered after a withdrawal, here or in another tab, does not bring the consent back.
 * @param events Where the consent API dispatches the changes, the document in the page.
 * @param url    The URL of the renewal endpoint.
 * @param renew  Sends the request.
 * @param forget Removes the consent cookie.
 */
export function renewConsentCookie(
	events: EventTarget,
	url: string,
	renew: Renew,
	forget: () => void
): void {
	let withdrawn = false;
	let renewals: Promise< void > = Promise.resolve();

	events.addEventListener( CONSENT_CHANGED, ( event ) => {
		const detail: unknown =
			event instanceof CustomEvent ? event.detail : null;
		if ( ! isChange( detail ) ) {
			return;
		}

		withdrawn = detail.consent === null;
		if ( withdrawn || detail.fromAnotherTab ) {
			return;
		}

		renewals = renewals
			.then( () => renew( url ) )
			.then( () => {
				if ( withdrawn ) {
					forget();
				}
			} );
	} );
}

/**
 * With the cookies of the site: the server reads the consent cookie and sends it back, no body is trusted.
 * @param url The URL of the renewal endpoint.
 */
export const renewWithFetch: Renew = ( url ) =>
	fetch( url, {
		method: 'POST',
		keepalive: true,
		credentials: 'same-origin',
	} ).then(
		() => undefined,
		() => undefined
	);
