import { parseMapping } from '../consent-mode/consent-mode';
import type { ConsentApi } from '../consent/types';

/** Dispatched on the document when the consent type is defined, as the WP Consent API asks. */
export const CONSENT_TYPE_DEFINED = 'wp_consent_type_defined';

export type ConsentValue = 'allow' | 'deny';

/** The wp_set_consent() function of the WP Consent API. */
export type SetConsent = ( category: string, value: ConsentValue ) => void;

/**
 * Printed by ItalyCookieChoices\Integrations\UI\WpConsentApiScript.
 */
export interface WpConsentApiConfig {
	consentType: string;
	/** The categories of the WP Consent API each category allows, keyed by category id. */
	mapping: Record< string, string[] >;
}

/** The window, where the WP Consent API reads the consent type. */
export interface ConsentTypeHolder {
	wp_consent_type?: string;
	document: EventTarget;
}

/**
 * Every category of the WP Consent API in the mapping, allowed when an accepted category maps to it.
 * @param mapping    The categories of the WP Consent API keyed by category id.
 * @param categories The accepted categories.
 */
export function consentValues(
	mapping: Record< string, string[] >,
	categories: readonly string[]
): Record< string, ConsentValue > {
	const values: Record< string, ConsentValue > = {};
	for ( const apiCategories of Object.values( mapping ) ) {
		for ( const apiCategory of apiCategories ) {
			values[ apiCategory ] = 'deny';
		}
	}

	for ( const category of categories ) {
		for ( const apiCategory of mapping[ category ] ?? [] ) {
			values[ apiCategory ] = 'allow';
		}
	}

	return values;
}

/**
 * Reads the configuration printed in the page, opt-in when the consent type is missing.
 * @param value The value of window.italyCookieChoicesWpConsentApi.
 */
export function parseWpConsentApiConfig( value: unknown ): WpConsentApiConfig {
	const raw =
		typeof value === 'object' && value !== null
			? ( value as Record< string, unknown > )
			: {};

	return {
		consentType:
			typeof raw.consentType === 'string' && raw.consentType !== ''
				? raw.consentType
				: 'optin',
		mapping: parseMapping( raw.mapping ),
	};
}

function writeConsent(
	config: WpConsentApiConfig,
	categories: readonly string[],
	setConsent: SetConsent
): void {
	const values = consentValues( config.mapping, categories );
	for ( const [ apiCategory, value ] of Object.entries( values ) ) {
		setConsent( apiCategory, value );
	}
}

/**
 * Defines the consent type, then writes the stored consent and each change to the WP Consent API.
 *
 * Without a consent the required categories are allowed and the others denied,
 * so the values left by an expired or withdrawn consent are replaced.
 * @param config     The WP Consent API configuration.
 * @param consent    The consent API of the plugin.
 * @param setConsent The wp_set_consent() function, it dispatches its event only when a value changes.
 * @param holder     The window.
 */
export function startWpConsentApi(
	config: WpConsentApiConfig,
	consent: ConsentApi,
	setConsent: SetConsent,
	holder: ConsentTypeHolder
): void {
	holder.wp_consent_type = config.consentType;
	holder.document.dispatchEvent( new CustomEvent( CONSENT_TYPE_DEFINED ) );

	const required = consent
		.categories()
		.filter( ( category ) => category.required )
		.map( ( category ) => category.id );

	writeConsent(
		config,
		consent.getConsent()?.categories ?? required,
		setConsent
	);
	consent.onChange( ( change ) => {
		writeConsent(
			config,
			change.consent?.categories ?? required,
			setConsent
		);
	} );
}
