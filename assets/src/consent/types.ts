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
	/** The endpoint that renews the consent cookie from the server, missing when it is not available. */
	renewalUrl?: string;
	/** The domain of the consent cookie, like example.com for its subdomains, missing for the host only. */
	cookieDomain?: string;
	/** Whether the visitors choose the single vendors inside the categories. */
	vendorConsent?: boolean;
	/** The vendors blocked by the plugin, with the consent per vendor. */
	vendors?: Vendor[];
	/** A page in scan mode, for the scan of the cookies: every category accepted in memory. */
	scan?: true;
}

export interface Vendor {
	id: string;
	category: string;
}

export interface Consent {
	/** A random UUID kept by the browser, it links the choices of the same visitor in the consent log. */
	id: string;
	categories: string[];
	version: string;
	/** ISO 8601 date, when the consent was given. */
	date: string;
	/** The vendors refused inside the accepted categories, with the consent per vendor. */
	refusedVendors?: string[];
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
	/** The consent to a vendor: its category is accepted and, with the consent per vendor, the vendor is not refused. */
	hasVendorConsent: ( vendor: string, category: string ) => boolean;
	/**
	 * Saves the accepted categories, the required ones are always included, and the vendors refused inside them.
	 */
	setConsent: ( categories: string[], refusedVendors?: string[] ) => Consent;
	/** Accepts a single vendor and its category, the other vendors of a category not accepted yet are refused. */
	acceptVendor: ( vendor: string, category: string ) => Consent;
	acceptAll: () => Consent;
	rejectAll: () => Consent;
	withdraw: () => void;
	/** Calls the listener when the consent changes, returns a function that removes it. */
	onChange: ( listener: ConsentListener ) => () => void;
}
