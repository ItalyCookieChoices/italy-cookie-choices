import { lastWrites, type RecordedCookie } from '../recorded';
import { cookieOfStore, cookieOfWrite, recordCookies } from '../recorder';

const NOW = Date.parse( '2026-11-22T10:00:00Z' );

describe( 'cookieOfWrite', () => {
	it( 'reads the name, the path and the duration of Max-Age', () => {
		expect(
			cookieOfWrite(
				'_ga=GA1.1.1; max-age=63072000; path=/; SameSite=Lax',
				NOW
			)
		).toEqual( {
			name: '_ga',
			path: '/',
			duration: 63072000,
			httpOnly: false,
		} );
	} );

	it( 'reads the duration of Expires', () => {
		expect(
			cookieOfWrite(
				'lang=it; expires=Sun, 22 Nov 2026 11:00:00 GMT',
				NOW
			)
		).toEqual( {
			name: 'lang',
			path: '',
			duration: 3600,
			httpOnly: false,
		} );
	} );

	it( 'prefers Max-Age to Expires', () => {
		expect(
			cookieOfWrite(
				'a=1; Expires=Sun, 22 Nov 2026 11:00:00 GMT; Max-Age=60',
				NOW
			)?.duration
		).toBe( 60 );
	} );

	it( 'reads a session cookie', () => {
		expect( cookieOfWrite( 'a=1', NOW )?.duration ).toBeNull();
	} );

	it( 'skips the removals and the writes without a name', () => {
		expect( cookieOfWrite( 'a=; max-age=0', NOW ) ).toBeNull();
		expect(
			cookieOfWrite( 'a=; expires=Thu, 01 Jan 1970 00:00:00 GMT', NOW )
		).toBeNull();
		expect( cookieOfWrite( '=value', NOW ) ).toBeNull();
		expect( cookieOfWrite( 'novalue', NOW ) ).toBeNull();
	} );
} );

describe( 'cookieOfStore', () => {
	it( 'reads the options of cookieStore.set()', () => {
		expect(
			cookieOfStore(
				{
					name: '_clck',
					value: 'x',
					expires: NOW + 86400000,
					path: '/',
				},
				NOW
			)
		).toEqual( {
			name: '_clck',
			path: '/',
			duration: 86400,
			httpOnly: false,
		} );
	} );

	it( 'reads a session cookie set by name', () => {
		expect( cookieOfStore( 'a', NOW ) ).toEqual( {
			name: 'a',
			path: '/',
			duration: null,
			httpOnly: false,
		} );
	} );

	it( 'skips the removals', () => {
		expect(
			cookieOfStore( { name: 'a', value: '', expires: NOW - 1000 }, NOW )
		).toBeNull();
	} );
} );

describe( 'recordCookies', () => {
	afterEach( () => {
		// The recorder defines the property on the document itself.
		Reflect.deleteProperty( document, 'cookie' );
		document.cookie = 'a=; max-age=0';
	} );

	it( 'records the writes of document.cookie, and the browser still gets them', () => {
		const recorded: RecordedCookie[] = [];
		recordCookies( document, undefined, recorded, () => NOW );

		document.cookie = 'a=1; max-age=60; path=/';

		expect( recorded ).toEqual( [
			{ name: 'a', path: '/', duration: 60, httpOnly: false },
		] );
		expect( document.cookie ).toContain( 'a=1' );
	} );

	it( 'records the cookies of cookieStore.set()', async () => {
		const recorded: RecordedCookie[] = [];
		const set = jest.fn( () => Promise.resolve() );
		const store = { set } as unknown as CookieStore;
		recordCookies( document, store, recorded, () => NOW );

		await store.set( 'b', '2' );

		expect( recorded ).toEqual( [
			{ name: 'b', path: '/', duration: null, httpOnly: false },
		] );
		expect( set ).toHaveBeenCalledWith( 'b', '2' );
	} );
} );

describe( 'lastWrites', () => {
	it( 'keeps the last write of each cookie', () => {
		const first = { name: 'a', path: '/', duration: 30, httpOnly: false };
		const other = { name: 'b', path: '/', duration: null, httpOnly: false };
		const last = { ...first, duration: 60 };

		expect( lastWrites( [ first, other, last ] ) ).toEqual( [
			other,
			last,
		] );
	} );
} );
