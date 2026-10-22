import { CONSENT_CHANGED, type ConsentApi, type ConsentChange } from './types';

export const CHANNEL = 'italy-cookie-choices';

/**
 * Tells the other open tabs of the site when the consent changes, they share the cookie but not the page state.
 *
 * @param api     The consent API.
 * @param events  Where the change events are dispatched, the document in the page.
 * @param channel The channel shared by the tabs, null when the browser does not support it.
 */
export function syncTabs(
	api: ConsentApi,
	events: EventTarget,
	channel: BroadcastChannel | null
): void {
	if ( channel === null ) {
		return;
	}

	api.onChange( ( change ) => {
		if ( ! change.fromAnotherTab ) {
			channel.postMessage( {
				consent: change.consent,
				previous: change.previous,
			} );
		}
	} );

	channel.addEventListener( 'message', ( message: MessageEvent ) => {
		const data = message.data as Partial< ConsentChange > | null;
		events.dispatchEvent(
			new CustomEvent< ConsentChange >( CONSENT_CHANGED, {
				detail: {
					consent: data?.consent ?? null,
					previous: data?.previous ?? null,
					fromAnotherTab: true,
				},
			} )
		);
	} );
}
