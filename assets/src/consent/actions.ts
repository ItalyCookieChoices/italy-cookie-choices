import type { ConsentApi } from './types';

export const ACCEPT = 'data-icc-accept';
export const ACCEPT_ALL = 'data-icc-accept-all';

/**
 * Buttons anywhere in the page give the consent:
 * <button data-icc-accept="marketing"> adds a category, <button data-icc-accept-all> accepts every category.
 *
 * @param document The page.
 * @param api      The consent API.
 */
export function listenToConsentButtons(
	document: Document,
	api: ConsentApi
): void {
	document.addEventListener( 'click', ( event ) => {
		const target = event.target instanceof Element ? event.target : null;
		const button = target?.closest( `[${ ACCEPT }], [${ ACCEPT_ALL }]` );
		if ( button === null || button === undefined ) {
			return;
		}

		event.preventDefault();
		if ( button.hasAttribute( ACCEPT_ALL ) ) {
			api.acceptAll();
			return;
		}

		const accepted = api.getConsent()?.categories ?? [];
		api.setConsent( [ ...accepted, button.getAttribute( ACCEPT ) ?? '' ] );
	} );
}
