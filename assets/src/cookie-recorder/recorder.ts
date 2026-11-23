import type { RecordedCookie } from './recorded';

const SECOND = 1000;

type StoreSet = (
	nameOrOptions: string | CookieInit,
	value?: string
) => Promise< void >;

function attributesOf( parts: readonly string[] ): Map< string, string > {
	const attributes = new Map< string, string >();
	for ( const part of parts ) {
		const separator = part.indexOf( '=' );
		const name = separator === -1 ? part : part.slice( 0, separator );
		attributes.set(
			name.trim().toLowerCase(),
			separator === -1 ? '' : part.slice( separator + 1 ).trim()
		);
	}

	return attributes;
}

function durationOf(
	attributes: Map< string, string >,
	now: number
): number | null {
	const maxAge = attributes.get( 'max-age' );
	if ( maxAge !== undefined ) {
		return Number.parseInt( maxAge, 10 );
	}

	const expires = attributes.get( 'expires' );
	if ( expires === undefined ) {
		return null;
	}

	const date = Date.parse( expires );

	return Number.isNaN( date ) ? null : Math.round( ( date - now ) / SECOND );
}

function removes( duration: number | null ): boolean {
	return duration !== null && ( Number.isNaN( duration ) || duration <= 0 );
}

/**
 * The cookie of a write to document.cookie, null for a removal or a write without a name.
 * @param text The written text, like "_ga=GA1.1; max-age=63072000; path=/".
 * @param now  The current time, in milliseconds.
 */
export function cookieOfWrite(
	text: string,
	now: number
): RecordedCookie | null {
	const [ pair = '', ...parts ] = text.split( ';' );
	const separator = pair.indexOf( '=' );
	const name = separator === -1 ? '' : pair.slice( 0, separator ).trim();
	const attributes = attributesOf( parts );
	const duration = durationOf( attributes, now );
	if ( name === '' || removes( duration ) ) {
		return null;
	}

	return {
		name,
		path: attributes.get( 'path' ) ?? '',
		duration,
		httpOnly: false,
	};
}

/**
 * The cookie of a call to cookieStore.set(), null for a removal.
 * @param nameOrOptions The name of the cookie or its options.
 * @param now           The current time, in milliseconds.
 */
export function cookieOfStore(
	nameOrOptions: string | CookieInit,
	now: number
): RecordedCookie | null {
	const options: Pick< CookieInit, 'name' | 'expires' | 'path' > =
		typeof nameOrOptions === 'string'
			? { name: nameOrOptions }
			: nameOrOptions;
	const expires = options.expires ?? null;
	const duration =
		expires === null ? null : Math.round( ( expires - now ) / SECOND );
	if ( options.name === '' || removes( duration ) ) {
		return null;
	}

	return {
		name: options.name,
		path: options.path ?? '/',
		duration,
		httpOnly: false,
	};
}

/**
 * Records the cookies the scripts of the page write, with document.cookie and cookieStore.set(),
 * the writes still reach the browser.
 * @param document    The document of the page.
 * @param cookieStore The Cookie Store API, undefined when the browser lacks it.
 * @param recorded    Where the cookies are added.
 * @param now         The current time, in milliseconds.
 */
export function recordCookies(
	document: Document,
	cookieStore: CookieStore | undefined,
	recorded: RecordedCookie[],
	now: () => number = () => Date.now()
): void {
	const record = ( cookie: RecordedCookie | null ): void => {
		if ( cookie !== null ) {
			recorded.push( cookie );
		}
	};

	const descriptor = Object.getOwnPropertyDescriptor(
		Document.prototype,
		'cookie'
	);
	const { get, set } = descriptor ?? {};
	if ( get !== undefined && set !== undefined ) {
		Object.defineProperty( document, 'cookie', {
			configurable: true,
			get: () => get.call( document ),
			set: ( value: unknown ) => {
				record( cookieOfWrite( String( value ), now() ) );
				set.call( document, value );
			},
		} );
	}

	if ( cookieStore === undefined ) {
		return;
	}

	const storeSet = cookieStore.set.bind( cookieStore ) as StoreSet;
	const recordingSet: StoreSet = ( nameOrOptions, value ) => {
		record( cookieOfStore( nameOrOptions, now() ) );

		return storeSet( nameOrOptions, value );
	};
	cookieStore.set = recordingSet as CookieStore[ 'set' ];
}
