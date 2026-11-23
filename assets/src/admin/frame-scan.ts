/**
 * Loads a page of the site in scan mode in a hidden frame and reads the cookies it sets,
 * see ItalyCookieChoices\Scan\UI\CookieRecorder.
 */
import {
	lastWrites,
	RECORDED_COOKIES,
	SERVER_COOKIES,
	type RecordedCookie,
} from '../cookie-recorder/recorded';

/** The time the scripts that start after the load get to set their cookies. */
export const SETTLE_TIME = 3000;
export const TIMEOUT = 30000;

/**
 * Why a page could not be scanned:
 *
 * * refused, the page refused the frame, like with X-Frame-Options, or it redirected to another site;
 * * not-in-scan-mode, the page did not run in scan mode, like a page served by a cache;
 * * timeout, the page did not load in time.
 */
export type FrameScanFailure = 'refused' | 'not-in-scan-mode' | 'timeout';

export class FrameScanError extends Error {
	public constructor( public readonly reason: FrameScanFailure ) {
		super( reason );
	}
}

/**
 * Whether the browser loads the frame as an anonymous visitor, with the credentialless frames of Chrome and Edge.
 * Elsewhere the frame runs as the logged-in administrator.
 * @param frameClass           The class of the frames.
 * @param frameClass.prototype The prototype of the frames.
 */
export function loadsAsAnonymousVisitor(
	frameClass: { prototype: object } = HTMLIFrameElement
): boolean {
	return 'credentialless' in frameClass.prototype;
}

function isRecordedCookie( value: unknown ): value is RecordedCookie {
	if ( typeof value !== 'object' || value === null ) {
		return false;
	}

	const cookie = value as Record< string, unknown >;

	return (
		typeof cookie.name === 'string' &&
		typeof cookie.path === 'string' &&
		( cookie.duration === null || typeof cookie.duration === 'number' ) &&
		typeof cookie.httpOnly === 'boolean'
	);
}

function cookiesIn( value: unknown ): RecordedCookie[] {
	return Array.isArray( value ) ? value.filter( isRecordedCookie ) : [];
}

/**
 * The cookies of the page in the frame: of the server first, then of its scripts.
 * @param frameWindow The window of the frame.
 */
export function cookiesOf( frameWindow: Window | null ): RecordedCookie[] {
	let recorded: unknown;
	let server: unknown;
	try {
		// Reading a page of another origin throws, like the error page of a refused frame.
		const globals = frameWindow as unknown as Record< string, unknown >;
		recorded = globals[ RECORDED_COOKIES ];
		server = globals[ SERVER_COOKIES ];
	} catch {
		throw new FrameScanError( 'refused' );
	}

	if ( ! Array.isArray( recorded ) ) {
		throw new FrameScanError( 'not-in-scan-mode' );
	}

	return lastWrites( [ ...cookiesIn( server ), ...cookiesIn( recorded ) ] );
}

/**
 * Loads the page in a frame, waits for its scripts and reads its cookies, then removes the frame.
 * @param url       The address of the page in scan mode.
 * @param container Where the frame is added.
 * @param settle    The time after the load.
 * @param timeout   The time to load the page.
 */
export function scanInFrame(
	url: string,
	container: HTMLElement,
	settle: number = SETTLE_TIME,
	timeout: number = TIMEOUT
): Promise< RecordedCookie[] > {
	return new Promise( ( resolve, reject ) => {
		const frame = container.ownerDocument.createElement( 'iframe' );
		const finish = ( done: () => void ): void => {
			clearTimeout( timer );
			done();
			frame.remove();
		};
		const timer = setTimeout(
			() => finish( () => reject( new FrameScanError( 'timeout' ) ) ),
			timeout
		);

		frame.addEventListener(
			'load',
			() =>
				setTimeout(
					() =>
						finish( () => {
							try {
								resolve( cookiesOf( frame.contentWindow ) );
							} catch ( error ) {
								reject( error );
							}
						} ),
					settle
				),
			{ once: true }
		);
		// An anonymous visitor: no cookie of the administrator, in the browsers that support it.
		frame.setAttribute( 'credentialless', '' );
		frame.setAttribute( 'aria-hidden', 'true' );
		frame.tabIndex = -1;
		frame.className = 'icc-admin__scan-frame';
		frame.src = url;
		container.append( frame );
	} );
}
