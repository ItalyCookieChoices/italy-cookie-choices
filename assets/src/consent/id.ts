/**
 * A random version 4 UUID, it identifies the consent of a browser in the log without saying who it is.
 *
 * crypto.randomUUID() exists only on HTTPS, the fallback builds the same format from random bytes.
 * @param crypto The Web Crypto API.
 */
export function randomId( crypto: Crypto = window.crypto ): string {
	if ( typeof crypto.randomUUID === 'function' ) {
		return crypto.randomUUID();
	}

	const bytes = crypto.getRandomValues( new Uint8Array( 16 ) );
	// The version 4 in the high 4 bits of the byte 6, the variant 10 in the high 2 bits of the byte 8.
	bytes[ 6 ] = 0x40 + ( ( bytes[ 6 ] ?? 0 ) % 0x10 );
	bytes[ 8 ] = 0x80 + ( ( bytes[ 8 ] ?? 0 ) % 0x40 );
	const hex = Array.from( bytes, ( byte ) =>
		byte.toString( 16 ).padStart( 2, '0' )
	).join( '' );

	return [
		hex.slice( 0, 8 ),
		hex.slice( 8, 12 ),
		hex.slice( 12, 16 ),
		hex.slice( 16, 20 ),
		hex.slice( 20 ),
	].join( '-' );
}
