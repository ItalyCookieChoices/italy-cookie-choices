/**
 * Reads and writes the cookies of the current site.
 */
export class Cookies {
	public constructor(
		private readonly document: Document,
		private readonly secure: boolean
	) {}

	public read( name: string ): string | null {
		const prefix = `${ name }=`;
		const cookie = this.document.cookie
			.split( '; ' )
			.find( ( part ) => part.startsWith( prefix ) );

		return cookie === undefined ? null : cookie.substring( prefix.length );
	}

	public write( name: string, value: string, expires: Date ): void {
		const secure = this.secure ? '; Secure' : '';
		this.document.cookie = `${ name }=${ value }; expires=${ expires.toUTCString() }; path=/; SameSite=Lax${ secure }`;
	}

	public remove( name: string ): void {
		this.write( name, '', new Date( 0 ) );
	}
}
