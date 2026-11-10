import { Cookies } from '../cookies';

/**
 * A document that records the cookies written, jsdom ignores a domain that is not its own.
 */
function documentWithCookies(): { document: Document; written: string[] } {
	const written: string[] = [];
	const fake = {
		get cookie(): string {
			return '';
		},
		set cookie( value: string ) {
			written.push( value );
		},
	};

	return { document: fake as unknown as Document, written };
}

const EXPIRES = new Date( '2027-01-01T00:00:00Z' );

describe( 'Cookies', () => {
	it( 'writes a cookie of the host by default', () => {
		const { document, written } = documentWithCookies();

		new Cookies( document, true ).write( 'icc_consent', 'a', EXPIRES );

		expect( written ).toEqual( [
			`icc_consent=a; expires=${ EXPIRES.toUTCString() }; path=/; SameSite=Lax; Secure`,
		] );
	} );

	it( 'writes the cookie for the cookie domain, after removing the one of the host', () => {
		const { document, written } = documentWithCookies();

		new Cookies( document, false, 'example.com' ).write(
			'icc_consent',
			'a',
			EXPIRES
		);

		expect( written ).toEqual( [
			`icc_consent=; expires=${ new Date(
				0
			).toUTCString() }; path=/; SameSite=Lax`,
			`icc_consent=a; expires=${ EXPIRES.toUTCString() }; path=/; domain=example.com; SameSite=Lax`,
		] );
	} );

	it( 'removes the cookie of the host and the one of the cookie domain', () => {
		const { document, written } = documentWithCookies();

		new Cookies( document, false, 'example.com' ).remove( 'icc_consent' );

		const expired = new Date( 0 ).toUTCString();
		expect( written ).toEqual( [
			`icc_consent=; expires=${ expired }; path=/; SameSite=Lax`,
			`icc_consent=; expires=${ expired }; path=/; domain=example.com; SameSite=Lax`,
		] );
	} );
} );
