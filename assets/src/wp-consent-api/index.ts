/**
 * Italy Cookie Choices integration with the WP Consent API, loaded by
 * ItalyCookieChoices\Integrations\UI\WpConsentApiScript after the scripts of the plugin and of the API.
 */
import {
	parseWpConsentApiConfig,
	startWpConsentApi,
	type SetConsent,
} from './wp-consent-api';

declare global {
	interface Window {
		italyCookieChoicesWpConsentApi?: unknown;
		wp_consent_type?: string;
		wp_set_consent?: SetConsent;
	}
}

const consent = window.italyCookieChoices;
const setConsent = window.wp_set_consent;
if ( consent !== undefined && typeof setConsent === 'function' ) {
	startWpConsentApi(
		parseWpConsentApiConfig( window.italyCookieChoicesWpConsentApi ),
		consent,
		setConsent,
		window
	);
}
