import { CONSENT_CHANGED, type ConsentChange } from '../consent/types';

/**
 * The body of POST /wp-json/italy-cookie-choices/v1/consents.
 */
export interface LogEntry {
	id: string;
	categories: string[];
	version: string;
}

export type Send = ( url: string, entry: LogEntry ) => void;

/**
 * The entry of a change made in this tab, no category when the consent is withdrawn.
 * @param change The consent change.
 */
export function entryOf( change: ConsentChange ): LogEntry | null {
	if ( change.fromAnotherTab ) {
		return null;
	}

	const { consent, previous } = change;
	if ( consent !== null ) {
		return {
			id: consent.id,
			categories: [ ...consent.categories ],
			version: consent.version,
		};
	}

	if ( previous === null ) {
		return null;
	}

	return { id: previous.id, categories: [], version: previous.version };
}

/**
 * Sends each choice made in this tab to the consent log.
 * @param events Where the consent API dispatches the changes, the document in the page.
 * @param url    The URL of the log endpoint.
 * @param send   Sends the entry.
 */
export function logConsentChanges(
	events: EventTarget,
	url: string,
	send: Send
): void {
	events.addEventListener( CONSENT_CHANGED, ( event ) => {
		const entry = entryOf(
			( event as CustomEvent< ConsentChange > ).detail
		);
		if ( entry === null ) {
			return;
		}

		send( url, entry );
	} );
}

/**
 * Without cookies, the log does not need them, and it survives the reload after the choice.
 * @param url   The URL of the log endpoint.
 * @param entry The entry.
 */
export const sendWithFetch: Send = ( url, entry ) => {
	fetch( url, {
		method: 'POST',
		keepalive: true,
		credentials: 'omit',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify( entry ),
	} ).catch( () => undefined );
};
