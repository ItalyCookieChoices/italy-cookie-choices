/**
 * The cookies a page sets in scan mode, read by the settings page from the frame of the page.
 */
export interface RecordedCookie {
	name: string;
	/** The path of the cookie, empty for the path of the page. */
	path: string;
	/** How long the cookie is kept, in seconds, null for a session cookie. */
	duration: number | null;
	/** Whether the scripts cannot read it, only for the cookies of the server. */
	httpOnly: boolean;
}

/** The global variable of the cookies the scripts write, set by the recorder. */
export const RECORDED_COOKIES = 'italyCookieChoicesRecordedCookies';

/** The global variable of the cookies of the server, as CookieRecorder::SERVER_COOKIES in src/Scan/UI/CookieRecorder.php. */
export const SERVER_COOKIES = 'italyCookieChoicesServerCookies';

/**
 * The cookies with the last write of each one, by name and path.
 * @param cookies The cookies in the order of their writes.
 */
export function lastWrites(
	cookies: readonly RecordedCookie[]
): RecordedCookie[] {
	const byKey = new Map< string, RecordedCookie >();
	for ( const cookie of cookies ) {
		const key = `${ cookie.name };${ cookie.path }`;
		byKey.delete( key );
		byKey.set( key, cookie );
	}

	return [ ...byKey.values() ];
}
