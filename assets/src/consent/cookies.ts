/**
 * Where the consent API keeps the consent.
 */
export interface CookieJar {
	read: ( name: string ) => string | null;
	write: ( name: string, value: string, expires: Date ) => void;
	remove: ( name: string ) => void;
}

/**
 * Reads and writes the cookies of the current site.
 *
 * With a cookie domain, like example.com, the cookies are shared with its subdomains.
 * The cookie of the host written before the domain was set is removed, so only one is left.
 */
export class Cookies implements CookieJar {
	public constructor(
		private readonly document: Document,
		private readonly secure: boolean,
		private readonly domain: string = ''
	) {}

	public read( name: string ): string | null {
		const prefix = `${ name }=`;
		const cookie = this.document.cookie
			.split( '; ' )
			.find( ( part ) => part.startsWith( prefix ) );

		return cookie === undefined ? null : cookie.substring( prefix.length );
	}

	public write( name: string, value: string, expires: Date ): void {
		if ( this.domain !== '' ) {
			this.set( name, '', new Date( 0 ), '' );
		}

		this.set( name, value, expires, this.domain );
	}

	public remove( name: string ): void {
		this.write( name, '', new Date( 0 ) );
	}

	private set(
		name: string,
		value: string,
		expires: Date,
		domain: string
	): void {
		const domainAttribute = domain !== '' ? `; domain=${ domain }` : '';
		const secure = this.secure ? '; Secure' : '';
		this.document.cookie = `${ name }=${ value }; expires=${ expires.toUTCString() }; path=/${ domainAttribute }; SameSite=Lax${ secure }`;
	}
}

/**
 * Cookies kept in memory, never written to the browser: the consent of a page in scan mode.
 */
export class MemoryCookies implements CookieJar {
	private readonly values = new Map< string, string >();

	public read( name: string ): string | null {
		return this.values.get( name ) ?? null;
	}

	public write( name: string, value: string ): void {
		this.values.set( name, value );
	}

	public remove( name: string ): void {
		this.values.delete( name );
	}
}
