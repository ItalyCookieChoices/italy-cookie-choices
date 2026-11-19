import type { ConsentApi } from '../consent/types';

/**
 * Printed by ItalyCookieChoices\Cookies\UI\CookieCleanupScript.
 */
export interface CookieCleanupConfig {
	/** The names of the cookies of each optional category, a name ending with * is a prefix. */
	categories: Record< string, string[] >;
	/** The cookies of each vendor, with the consent per vendor, empty with the consent by category. */
	vendors: VendorCookies[];
}

export interface VendorCookies {
	id: string;
	category: string;
	/** The names of the cookies, a name ending with * is a prefix. */
	cookies: string[];
}

/**
 * The choice of the visitor the cleanup follows.
 */
export interface CleanupChoice {
	categories: readonly string[];
	/** The vendors refused inside the accepted categories. */
	refusedVendors?: readonly string[];
}

function names( value: unknown ): string[] {
	return Array.isArray( value )
		? value.filter(
				( name ): name is string =>
					typeof name === 'string' && name !== ''
			)
		: [];
}

function parseCategories( raw: unknown ): Record< string, string[] > {
	const categories: Record< string, string[] > = {};
	if ( typeof raw !== 'object' || raw === null ) {
		return categories;
	}

	for ( const [ category, list ] of Object.entries( raw ) ) {
		if ( Array.isArray( list ) ) {
			categories[ category ] = names( list );
		}
	}

	return categories;
}

function parseVendor( value: unknown ): VendorCookies | null {
	if ( typeof value !== 'object' || value === null ) {
		return null;
	}

	const { id, category, cookies } = value as Record< string, unknown >;
	if (
		typeof id !== 'string' ||
		typeof category !== 'string' ||
		! Array.isArray( cookies )
	) {
		return null;
	}

	return { id, category, cookies: names( cookies ) };
}

/**
 * Reads the configuration printed in the page, the invalid values are skipped.
 * @param value The value of window.italyCookieChoicesCookieCleanup.
 */
export function parseCookieCleanupConfig(
	value: unknown
): CookieCleanupConfig {
	const raw =
		typeof value === 'object' && value !== null
			? ( value as Record< string, unknown > )
			: {};

	return {
		categories: parseCategories( raw.categories ),
		vendors: ( Array.isArray( raw.vendors ) ? raw.vendors : [] )
			.map( parseVendor )
			.filter( ( vendor ): vendor is VendorCookies => vendor !== null ),
	};
}

function matches( pattern: string, name: string ): boolean {
	return pattern.endsWith( '*' )
		? name.startsWith( pattern.slice( 0, -1 ) )
		: name === pattern;
}

function matchesAny( patterns: readonly string[], name: string ): boolean {
	return patterns.some( ( pattern ) => matches( pattern, name ) );
}

/**
 * The names of the cookies of the refused vendors and of the vendors still accepted: a name they share is kept.
 * @param config The cookies of each vendor.
 * @param choice The choice of the visitor.
 */
function vendorPatterns(
	config: CookieCleanupConfig,
	choice: CleanupChoice
): { refused: string[]; kept: string[] } {
	const refusedVendors = choice.refusedVendors ?? [];
	const isRefused = ( vendor: VendorCookies ): boolean =>
		refusedVendors.includes( vendor.id );
	const isKept = ( vendor: VendorCookies ): boolean =>
		choice.categories.includes( vendor.category ) && ! isRefused( vendor );

	return {
		refused: config.vendors
			.filter( isRefused )
			.flatMap( ( vendor ) => vendor.cookies ),
		kept: config.vendors
			.filter( isKept )
			.flatMap( ( vendor ) => vendor.cookies ),
	};
}

/**
 * The cookies of the browser that belong to a category without the consent or to a refused vendor.
 * @param config   The names of the cookies of each category and vendor.
 * @param choice   The accepted categories and the refused vendors.
 * @param existing The names of the cookies of the browser.
 */
export function cookiesToDelete(
	config: CookieCleanupConfig,
	choice: CleanupChoice,
	existing: readonly string[]
): string[] {
	const categories = Object.entries( config.categories )
		.filter( ( [ category ] ) => ! choice.categories.includes( category ) )
		.flatMap( ( [ , list ] ) => list );
	const vendors = vendorPatterns( config, choice );

	return existing.filter(
		( name ) =>
			matchesAny( categories, name ) ||
			( matchesAny( vendors.refused, name ) &&
				! matchesAny( vendors.kept, name ) )
	);
}

/**
 * The host, for the cookies without a domain, and the parent domains a script can set a cookie on.
 * @param hostname The host of the page.
 */
export function domainsOf( hostname: string ): string[] {
	const labels = hostname.split( '.' );
	if ( labels.length < 2 ) {
		return [ hostname ];
	}

	const parents: string[] = [];
	for ( let start = 0; start < labels.length - 1; start++ ) {
		parents.push( `.${ labels.slice( start ).join( '.' ) }` );
	}

	return [ hostname, ...parents ];
}

function cookieNames( document: Document ): string[] {
	return document.cookie
		.split( '; ' )
		.map( ( cookie ) => cookie.split( '=' )[ 0 ] ?? '' )
		.filter( ( name ) => name !== '' );
}

function deleteCookie(
	document: Document,
	name: string,
	hostname: string
): void {
	const expired = `${ name }=; expires=${ new Date( 0 ).toUTCString() }; path=/`;
	for ( const domain of domainsOf( hostname ) ) {
		document.cookie =
			domain === hostname ? expired : `${ expired }; domain=${ domain }`;
	}
}

/**
 * Deletes the cookies of the categories without the consent and of the refused vendors, on load and after
 * each choice, the ones made in another tab too.
 *
 * Only the cookies the script can see: the HttpOnly ones and the ones of the other sites are not reachable.
 * @param config   The names of the cookies of each category and vendor.
 * @param consent  The consent API of the plugin.
 * @param document The document of the page.
 * @param hostname The host of the page.
 */
export function startCookieCleanup(
	config: CookieCleanupConfig,
	consent: ConsentApi,
	document: Document,
	hostname: string
): void {
	const required = consent
		.categories()
		.filter( ( category ) => category.required )
		.map( ( category ) => category.id );

	const clean = ( choice: CleanupChoice | null ): void => {
		cookiesToDelete(
			config,
			choice ?? { categories: required },
			cookieNames( document )
		).forEach( ( name ) => deleteCookie( document, name, hostname ) );
	};

	clean( consent.getConsent() );
	consent.onChange( ( change ) => {
		clean( change.consent );
	} );
}
