export const CONSENT_CHANGED = 'italy-cookie-choices:consent-changed';
export const READY = 'italy-cookie-choices:ready';

export interface Category {
	id: string;
	required: boolean;
}

export interface LegacyCookie {
	name: string;
	value: string;
}

/**
 * Printed by ItalyCookieChoices\Consent\UI\ConsentScript.
 */
export interface ConsentConfig {
	cookieName: string;
	categories: Category[];
	version: string;
	durationDays: number;
	legacyCookie: LegacyCookie | null;
}

export interface Consent {
	/** A random UUID kept by the browser, it links the choices of the same visitor in the consent log. */
	id: string;
	categories: string[];
	version: string;
	/** ISO 8601 date, when the consent was given. */
	date: string;
}

export interface ConsentChange {
	consent: Consent | null;
	previous: Consent | null;
	/** True when the visitor changed the consent in another tab of the site. */
	fromAnotherTab: boolean;
}

export type ConsentListener = ( change: ConsentChange ) => void;

export interface ConsentApi {
	categories: () => Category[];
	/** The consent, null when it is missing, given for another policy version or expired. */
	getConsent: () => Consent | null;
	hasConsent: ( category: string ) => boolean;
	/** Saves the accepted categories, the required ones are always included. */
	setConsent: ( categories: string[] ) => Consent;
	acceptAll: () => Consent;
	rejectAll: () => Consent;
	withdraw: () => void;
	/** Calls the listener when the consent changes, returns a function that removes it. */
	onChange: ( listener: ConsentListener ) => () => void;
}
