/**
 * The scan of the cookies in the browser: the pages are loaded in a frame in scan mode,
 * the cookies they set are merged in a report and matched with the known cookies.
 */
import { _n, __, sprintf } from '@wordpress/i18n';
import type { RecordedCookie } from '../cookie-recorder/recorded';
import type { CustomVendor, SiteCookie, Vendor } from './settings';

export interface FoundCookie extends RecordedCookie {
	/** The pages that set it. */
	pages: string[];
}

export interface CookieScanReport {
	/** The cookies found, by name. */
	cookies: Record< string, FoundCookie >;
	errors: Array< { url: string; error: string } >;
	scanned: number;
}

/** Who sets a cookie found: a vendor, the site, or nobody known. */
export type CookieOwner =
	{ kind: 'vendor'; name: string } | { kind: 'site' } | { kind: 'unknown' };

const MINUTE = 60;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;
const DAYS_IN_YEAR = 365;
const DAYS_IN_MONTH = 30.44;
/** The days a duration can be away from whole years, like the 400 days of some cookies. */
const YEAR_TOLERANCE = 7;
/** The durations shorter than this are written in days. */
const DAYS_LIMIT = 90;

export function emptyCookieReport(): CookieScanReport {
	return { cookies: {}, errors: [], scanned: 0 };
}

/**
 * The report with the cookies of a page, a cookie keeps its last duration.
 * @param report  The report of the pages before.
 * @param url     The address of the page.
 * @param cookies The cookies the page set.
 */
export function withPageCookies(
	report: CookieScanReport,
	url: string,
	cookies: readonly RecordedCookie[]
): CookieScanReport {
	const merged = { ...report.cookies };
	for ( const cookie of cookies ) {
		const pages = merged[ cookie.name ]?.pages ?? [];
		merged[ cookie.name ] = {
			...cookie,
			pages: pages.includes( url ) ? pages : [ ...pages, url ],
		};
	}

	return { ...report, cookies: merged, scanned: report.scanned + 1 };
}

/**
 * The report with a page that could not be scanned.
 * @param report The report of the pages before.
 * @param url    The address of the page.
 * @param error  Why it could not be scanned.
 */
export function withPageError(
	report: CookieScanReport,
	url: string,
	error: string
): CookieScanReport {
	return {
		...report,
		errors: [ ...report.errors, { url, error } ],
		scanned: report.scanned + 1,
	};
}

/**
 * Whether a cookie name matches the name of a known cookie, that ends with * for a prefix, like _ga_*.
 * @param known The name of the known cookie.
 * @param name  The name of the cookie found.
 */
export function matchesCookie( known: string, name: string ): boolean {
	return known.endsWith( '*' )
		? name.startsWith( known.slice( 0, -1 ) )
		: known === name;
}

/**
 * Who sets a cookie, from the cookies of the site and of the vendors.
 * @param name          The name of the cookie found.
 * @param vendors       The common vendors, with their cookies.
 * @param customVendors The custom vendors, with their cookies.
 * @param siteCookies   The cookies of the site.
 */
export function ownerOf(
	name: string,
	vendors: readonly Vendor[],
	customVendors: readonly CustomVendor[],
	siteCookies: readonly SiteCookie[]
): CookieOwner {
	if (
		siteCookies.some( ( cookie ) => matchesCookie( cookie.name, name ) )
	) {
		return { kind: 'site' };
	}

	const vendor =
		vendors.find( ( preset ) =>
			( preset.cookies ?? [] ).some( ( known ) =>
				matchesCookie( known, name )
			)
		) ??
		customVendors.find( ( custom ) =>
			( custom.cookies ?? [] ).some( ( cookie ) =>
				matchesCookie( cookie.name, name )
			)
		);

	return vendor === undefined
		? { kind: 'unknown' }
		: { kind: 'vendor', name: vendor.name };
}

/**
 * How long a cookie is kept, as the cookie policy writes it,
 * with the texts of ItalyCookieChoices\Cookies\Application\CookieDurations.
 * @param seconds The duration in seconds, null for a session cookie.
 */
export function durationText( seconds: number | null ): string {
	if ( seconds === null ) {
		return __( 'Session', 'italy-cookie-choices' );
	}

	if ( seconds < HOUR ) {
		const minutes = Math.max( 1, Math.round( seconds / MINUTE ) );
		return sprintf(
			/* translators: %d: the number of minutes. */
			_n( '%d minute', '%d minutes', minutes, 'italy-cookie-choices' ),
			minutes
		);
	}

	if ( seconds < DAY ) {
		const hours = Math.round( seconds / HOUR );
		return sprintf(
			/* translators: %d: the number of hours. */
			_n( '%d hour', '%d hours', hours, 'italy-cookie-choices' ),
			hours
		);
	}

	const days = Math.round( seconds / DAY );
	if ( days < DAYS_LIMIT ) {
		return sprintf(
			/* translators: %d: the number of days. */
			_n( '%d day', '%d days', days, 'italy-cookie-choices' ),
			days
		);
	}

	const years = Math.round( days / DAYS_IN_YEAR );
	if ( Math.abs( days - years * DAYS_IN_YEAR ) <= YEAR_TOLERANCE ) {
		return sprintf(
			/* translators: %d: the number of years. */
			_n( '%d year', '%d years', years, 'italy-cookie-choices' ),
			years
		);
	}

	const months = Math.round( days / DAYS_IN_MONTH );
	return sprintf(
		/* translators: %d: the number of months. */
		_n( '%d month', '%d months', months, 'italy-cookie-choices' ),
		months
	);
}
