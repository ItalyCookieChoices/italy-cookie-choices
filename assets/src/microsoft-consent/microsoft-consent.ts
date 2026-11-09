import { consentState, parseMapping } from '../consent-mode/consent-mode';
import { parseConfig } from '../consent/config';
import type { ConsentApi, ConsentConfig } from '../consent/types';

/** The consent types each category grants, keyed by category id. */
export type Mapping = Record< string, string[] >;

/**
 * Printed by ItalyCookieChoices\Integrations\UI\MicrosoftConsentScript, a signal is null when it is turned off.
 */
export interface MicrosoftConsentConfig {
	consent: ConsentConfig;
	uet: Mapping | null;
	clarity: Mapping | null;
}

/** The window.uetq queue of the UET tag, an array until the tag loads. */
export interface UetQueue {
	push: ( ...args: unknown[] ) => unknown;
}

/** The window.clarity function, a queue until Clarity loads. */
export interface Clarity {
	( ...args: unknown[] ): void;
	q?: unknown[];
}

function mappingFrom( value: unknown ): Mapping | null {
	return typeof value === 'object' && value !== null
		? parseMapping( value )
		: null;
}

/**
 * Reads the configuration printed in the page, the signals without a mapping are off.
 * @param value The value of window.italyCookieChoicesMicrosoftConsent.
 */
export function parseMicrosoftConsentConfig(
	value: unknown
): MicrosoftConsentConfig {
	const raw =
		typeof value === 'object' && value !== null
			? ( value as Record< string, unknown > )
			: {};

	return {
		consent: parseConfig( raw.consent ),
		uet: mappingFrom( raw.uet ),
		clarity: mappingFrom( raw.clarity ),
	};
}

function requiredIds( consent: ConsentApi ): string[] {
	return consent
		.categories()
		.filter( ( category ) => category.required )
		.map( ( category ) => category.id );
}

/**
 * Microsoft Advertising: the ad storage is denied by default, then the stored consent and each change update it.
 * @param mapping The UET consent types each category grants.
 * @param consent The consent API of the plugin.
 * @param uetq    The window.uetq queue.
 */
export function startUetConsent(
	mapping: Mapping,
	consent: ConsentApi,
	uetq: UetQueue
): void {
	const required = requiredIds( consent );

	uetq.push( 'consent', 'default', consentState( mapping, required ) );

	const stored = consent.getConsent();
	if ( stored !== null ) {
		uetq.push(
			'consent',
			'update',
			consentState( mapping, stored.categories )
		);
	}

	consent.onChange( ( change ) => {
		uetq.push(
			'consent',
			'update',
			consentState( mapping, change.consent?.categories ?? required )
		);
	} );
}

/**
 * Microsoft Clarity: the stored consent, denied without one, then each change, with the Consent API v2.
 * @param mapping The Clarity consent types each category grants.
 * @param consent The consent API of the plugin.
 * @param clarity The window.clarity function.
 */
export function startClarityConsent(
	mapping: Mapping,
	consent: ConsentApi,
	clarity: Clarity
): void {
	const required = requiredIds( consent );

	clarity(
		'consentv2',
		consentState( mapping, consent.getConsent()?.categories ?? required )
	);

	consent.onChange( ( change ) => {
		clarity(
			'consentv2',
			consentState( mapping, change.consent?.categories ?? required )
		);
	} );
}

/**
 * The queue of the Clarity snippet: Clarity runs the queued calls when it loads.
 */
export function clarityQueue(): Clarity {
	const clarity: Clarity = function () {
		// Clarity reads the arguments object, as its snippet queues it.
		// eslint-disable-next-line prefer-rest-params
		( clarity.q = clarity.q ?? [] ).push( arguments );
	};

	return clarity;
}
