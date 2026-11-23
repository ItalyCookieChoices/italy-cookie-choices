import {
	cookiesOf,
	FrameScanError,
	loadsAsAnonymousVisitor,
	scanInFrame,
} from '../frame-scan';

const RECORDED = { name: '_ga', path: '/', duration: 60, httpOnly: false };
const SERVER = { name: 'session', path: '/', duration: null, httpOnly: true };

function frameWindow( globals: Record< string, unknown > ): Window {
	return globals as unknown as Window;
}

describe( 'loadsAsAnonymousVisitor', () => {
	it( 'needs the credentialless frames', () => {
		expect(
			loadsAsAnonymousVisitor( { prototype: { credentialless: false } } )
		).toBe( true );
		expect( loadsAsAnonymousVisitor( { prototype: {} } ) ).toBe( false );
	} );
} );

describe( 'cookiesOf', () => {
	it( 'reads the cookies of the server and of the scripts, the last write wins', () => {
		const rewritten = { ...RECORDED, duration: 120 };

		expect(
			cookiesOf(
				frameWindow( {
					italyCookieChoicesServerCookies: [ SERVER, { name: 1 } ],
					italyCookieChoicesRecordedCookies: [ RECORDED, rewritten ],
				} )
			)
		).toEqual( [ SERVER, rewritten ] );
	} );

	it( 'fails when the page is of another origin', () => {
		const refused = new Proxy(
			{},
			{
				get: () => {
					throw new DOMException( 'Blocked', 'SecurityError' );
				},
			}
		);

		expect( () => cookiesOf( refused as Window ) ).toThrow(
			new FrameScanError( 'refused' )
		);
	} );

	it( 'fails when the page is not in scan mode', () => {
		expect( () => cookiesOf( frameWindow( {} ) ) ).toThrow(
			new FrameScanError( 'not-in-scan-mode' )
		);
	} );
} );

describe( 'scanInFrame', () => {
	beforeEach( () => jest.useFakeTimers() );
	afterEach( () => jest.useRealTimers() );

	it( 'reads the cookies after the load and the settle time, then removes the frame', async () => {
		const container = document.createElement( 'div' );
		const result = scanInFrame(
			'https://example.com/?icc_scan=t',
			container,
			100,
			1000
		);
		const frame = container.querySelector( 'iframe' );
		expect( frame?.getAttribute( 'src' ) ).toBe(
			'https://example.com/?icc_scan=t'
		);
		expect( frame?.hasAttribute( 'credentialless' ) ).toBe( true );
		Object.defineProperty( frame, 'contentWindow', {
			value: frameWindow( {
				italyCookieChoicesRecordedCookies: [ RECORDED ],
			} ),
		} );

		frame?.dispatchEvent( new Event( 'load' ) );
		jest.advanceTimersByTime( 100 );

		await expect( result ).resolves.toEqual( [ RECORDED ] );
		expect( container.querySelector( 'iframe' ) ).toBeNull();
	} );

	it( 'fails when the page does not load in time', async () => {
		const container = document.createElement( 'div' );
		const result = scanInFrame(
			'https://example.com/',
			container,
			100,
			1000
		);

		jest.advanceTimersByTime( 1000 );

		await expect( result ).rejects.toEqual(
			new FrameScanError( 'timeout' )
		);
		expect( container.querySelector( 'iframe' ) ).toBeNull();
	} );
} );
