import { createConsentApi } from '../consent/api';
import type { Cookies } from '../consent/cookies';
import {
	CONSENT_CHANGED,
	type ConsentChange,
	type ConsentConfig,
} from '../consent/types';

export type ConsentState = 'granted' | 'denied';

/**
 * Printed by ItalyCookieChoices\Integrations\UI\ConsentModeScript.
 */
export interface ConsentModeConfig {
	consent: ConsentConfig;
	/** The Google consent types each category grants, keyed by category id. */
	mapping: Record< string, string[] >;
	waitForUpdate: number;
}

export type Gtag = ( ...args: unknown[] ) => void;

/**
 * Every consent type of the mapping, granted when an accepted category grants it.
 * @param mapping    The consent types keyed by category id.
 * @param categories The accepted categories.
 */
export function consentState(
	mapping: Record< string, string[] >,
	categories: readonly string[]
): Record< string, ConsentState > {
	const state: Record< string, ConsentState > = {};
	for ( const types of Object.values( mapping ) ) {
		for ( const type of types ) {
			state[ type ] = 'denied';
		}
	}

	for ( const category of categories ) {
		for ( const type of mapping[ category ] ?? [] ) {
			state[ type ] = 'granted';
		}
	}

	return state;
}

/**
 * Reads the mapping printed in the page, the invalid values are skipped.
 * @param value The mapping of window.italyCookieChoicesConsentMode.
 */
export function parseMapping( value: unknown ): Record< string, string[] > {
	if ( typeof value !== 'object' || value === null ) {
		return {};
	}

	const mapping: Record< string, string[] > = {};
	for ( const [ category, types ] of Object.entries( value ) ) {
		if ( ! Array.isArray( types ) ) {
			continue;
		}

		mapping[ category ] = types.filter(
			( type ): type is string => typeof type === 'string'
		);
	}

	return mapping;
}

function isChange( value: unknown ): value is ConsentChange {
	return typeof value === 'object' && value !== null && 'consent' in value;
}

/**
 * Sends the default state, with only the required categories granted, then the stored consent and each change.
 * @param config  The Consent Mode configuration.
 * @param gtag    The Google tag function.
 * @param cookies Where the consent is stored.
 * @param events  Where the consent changes are dispatched, the document in the page.
 */
export function startConsentMode(
	config: ConsentModeConfig,
	gtag: Gtag,
	cookies: Cookies,
	events: EventTarget
): void {
	const required = config.consent.categories
		.filter( ( category ) => category.required )
		.map( ( category ) => category.id );

	gtag( 'consent', 'default', {
		...consentState( config.mapping, required ),
		wait_for_update: config.waitForUpdate,
	} );
	gtag( 'set', 'ads_data_redaction', true );

	const consent = createConsentApi(
		config.consent,
		cookies,
		events
	).getConsent();
	if ( consent !== null ) {
		gtag(
			'consent',
			'update',
			consentState( config.mapping, consent.categories )
		);
	}

	events.addEventListener( CONSENT_CHANGED, ( event ) => {
		const detail: unknown =
			event instanceof CustomEvent ? event.detail : null;
		if ( ! isChange( detail ) ) {
			return;
		}

		gtag(
			'consent',
			'update',
			consentState(
				config.mapping,
				detail.consent?.categories ?? required
			)
		);
	} );
}
