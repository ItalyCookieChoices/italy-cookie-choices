import type { ConsentApi } from '../consent/types';

/**
 * Printed by ItalyCookieChoices\Cookies\UI\CookieCleanupScript.
 */
export interface CookieCleanupConfig {
	/** The names of the cookies of each optional category, a name ending with * is a prefix. */
	categories: Record< string, string[] >;
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
			? ( value as Record< string, unknown > ).categories
			: null;
	const categories: Record< string, string[] > = {};
	if ( typeof raw !== 'object' || raw === null ) {
		return { categories };
	}

	for ( const [ category, names ] of Object.entries( raw ) ) {
		if ( ! Array.isArray( names ) ) {
			continue;
		}

		categories[ category ] = names.filter(
			( name ): name is string => typeof name === 'string' && name !== ''
		);
	}

	return { categories };
}

function matches( pattern: string, name: string ): boolean {
	return pattern.endsWith( '*' )
		? name.startsWith( pattern.slice( 0, -1 ) )
		: name === pattern;
}

/**
 * The cookies of the browser that belong to a category without the consent.
 * @param config   The names of the cookies of each category.
 * @param accepted The accepted categories.
 * @param existing The names of the cookies of the browser.
 */
export function cookiesToDelete(
	config: CookieCleanupConfig,
	accepted: readonly string[],
	existing: readonly string[]
): string[] {
	const patterns = Object.entries( config.categories )
		.filter( ( [ category ] ) => ! accepted.includes( category ) )
		.flatMap( ( [ , names ] ) => names );

	return existing.filter( ( name ) =>
		patterns.some( ( pattern ) => matches( pattern, name ) )
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
 * Deletes the cookies of the categories without the consent, on load and after each choice.
 *
 * Only the cookies the script can see: the HttpOnly ones and the ones of the other sites are not reachable.
 * @param config   The names of the cookies of each category.
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

	const clean = ( accepted: readonly string[] ): void => {
		cookiesToDelete( config, accepted, cookieNames( document ) ).forEach(
			( name ) => deleteCookie( document, name, hostname )
		);
	};

	clean( consent.getConsent()?.categories ?? required );
	consent.onChange( ( change ) => {
		clean( change.consent?.categories ?? required );
	} );
}
