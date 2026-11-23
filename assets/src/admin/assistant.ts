/**
 * The suggestions of the scan assistant, see ItalyCookieChoices\Scan\Application\ScanAssistant:
 * the AI provider of the site suggests the unknown third parties and cookies, the admin accepts or discards them.
 */

export interface HostSuggestion {
	host: string;
	name: string;
	category: string;
	purpose: string;
	/** The id of the common vendor the host belongs to, empty for none. */
	vendor: string;
}

export interface CookieSuggestion {
	name: string;
	provider: string;
	category: string;
	purpose: string;
}

export interface ScanSuggestions {
	hosts: HostSuggestion[];
	cookies: CookieSuggestion[];
}

type Fields< Item > = { [ Key in keyof Item ]: string };

function isRecord( value: unknown ): value is Record< string, unknown > {
	return typeof value === 'object' && value !== null;
}

function recordsOf< Item extends Fields< Item > >(
	value: unknown,
	keys: ReadonlyArray< keyof Item & string >
): Item[] {
	if ( ! Array.isArray( value ) ) {
		return [];
	}

	return value
		.filter( isRecord )
		.filter( ( record ) =>
			keys.every( ( key ) => typeof record[ key ] === 'string' )
		) as unknown as Item[];
}

/**
 * Reads the reply of the REST API, the invalid suggestions are skipped.
 * @param raw The reply.
 */
export function parseSuggestions( raw: unknown ): ScanSuggestions {
	const data = isRecord( raw ) ? raw : {};

	return {
		hosts: recordsOf< HostSuggestion >( data.hosts, [
			'host',
			'name',
			'category',
			'purpose',
			'vendor',
		] ),
		cookies: recordsOf< CookieSuggestion >( data.cookies, [
			'name',
			'provider',
			'category',
			'purpose',
		] ),
	};
}

/**
 * The suggestions by host or by cookie name.
 * @param items The suggestions.
 * @param key   The name of the field that identifies them.
 */
export function byKey< Item, Key extends keyof Item & string >(
	items: readonly Item[],
	key: Key
): Record< string, Item > {
	return Object.fromEntries(
		items.map( ( item ) => [ String( item[ key ] ), item ] )
	);
}
