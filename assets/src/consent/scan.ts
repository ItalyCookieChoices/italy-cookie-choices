import { createConsentApi } from './api';
import { MemoryCookies } from './cookies';
import type { ConsentApi, ConsentConfig } from './types';

/**
 * The consent API of a page in scan mode, for the scan of the cookies in the browser:
 * every category is accepted in memory, before the other scripts listen, so the banner stays closed,
 * the consent log gets no change and the browser gets no consent cookie.
 * @param config The consent configuration.
 * @param events Where the change events are dispatched, the document in the page.
 */
export function createScanApi(
	config: ConsentConfig,
	events: EventTarget
): ConsentApi {
	const api = createConsentApi( config, new MemoryCookies(), events );
	api.acceptAll();

	return api;
}
