/**
 * The scan of the site, see the Scan context in src/Scan: the pages are scanned in batches,
 * the results of the batches are merged in a report.
 */
import { _n, sprintf } from '@wordpress/i18n';

export interface ScanSelection {
	home: boolean;
	postTypes: string[];
	categories: number[];
	posts: number[];
}

export interface ScanTarget {
	id: number;
	title: string;
	url: string;
}

export interface ScannedPage {
	id: number;
	url: string;
	vendors: string[];
	hosts: string[];
	error: string;
}

export interface ScanReport {
	/** The pages where each vendor is, by vendor id. */
	vendors: Record< string, string[] >;
	/** The pages where each unknown third party is, by host. */
	hosts: Record< string, string[] >;
	errors: Array< { url: string; error: string } >;
	scanned: number;
}

export const BATCH = 10;

/**
 * The number of pages where something is found.
 * @param pages The addresses of the pages.
 */
export function pagesCount( pages: readonly string[] ): string {
	return sprintf(
		/* translators: %d: the number of pages. */
		_n( '%d page', '%d pages', pages.length, 'italy-cookie-choices' ),
		pages.length
	);
}

export function emptyReport(): ScanReport {
	return { vendors: {}, hosts: {}, errors: [], scanned: 0 };
}

function withPage(
	found: Record< string, string[] >,
	keys: readonly string[],
	url: string
): Record< string, string[] > {
	const merged = { ...found };
	for ( const key of keys ) {
		merged[ key ] = [ ...( merged[ key ] ?? [] ), url ];
	}

	return merged;
}

/**
 * The report with the pages of a batch.
 * @param report The report of the batches before.
 * @param pages  The pages of the batch.
 */
export function mergeScan(
	report: ScanReport,
	pages: readonly ScannedPage[]
): ScanReport {
	return pages.reduce< ScanReport >(
		( merged, page ) => ( {
			vendors: withPage( merged.vendors, page.vendors, page.url ),
			hosts: withPage( merged.hosts, page.hosts, page.url ),
			errors:
				page.error === ''
					? merged.errors
					: [
							...merged.errors,
							{ url: page.url, error: page.error },
						],
			scanned: merged.scanned + 1,
		} ),
		report
	);
}

/**
 * The ids of the targets in batches.
 * @param targets The pages to scan.
 */
export function batches( targets: readonly ScanTarget[] ): number[][] {
	const ids = targets.map( ( target ) => target.id );
	const result: number[][] = [];
	for ( let start = 0; start < ids.length; start += BATCH ) {
		result.push( ids.slice( start, start + BATCH ) );
	}

	return result;
}

export interface Term {
	id: number;
	name: string;
}

export type Frequency = 'off' | 'daily' | 'weekly' | 'monthly';

export interface ScheduleReport {
	date: string;
	pages: number;
	errors: number;
	vendors: string[];
	hosts: string[];
}

/** The schedule of the scan, printed by ItalyCookieChoices\Scan\UI\ScheduleData. */
export interface ScanSchedule {
	frequency: Frequency;
	home: boolean;
	postTypes: string[];
	categories: Term[];
	posts: Term[];
	email: boolean;
	adminEmail: string;
	report: ScheduleReport | null;
}

const FREQUENCIES: readonly Frequency[] = [
	'off',
	'daily',
	'weekly',
	'monthly',
];

function isRecord( value: unknown ): value is Record< string, unknown > {
	return (
		typeof value === 'object' && value !== null && ! Array.isArray( value )
	);
}

function strings( value: unknown ): string[] {
	return Array.isArray( value )
		? value.filter( ( item ): item is string => typeof item === 'string' )
		: [];
}

function terms( value: unknown ): Term[] {
	return Array.isArray( value )
		? value.filter(
				( term ): term is Term =>
					isRecord( term ) &&
					typeof term.id === 'number' &&
					typeof term.name === 'string'
			)
		: [];
}

function reportFrom( value: unknown ): ScheduleReport | null {
	if ( ! isRecord( value ) || typeof value.date !== 'string' ) {
		return null;
	}

	return {
		date: value.date,
		pages: typeof value.pages === 'number' ? value.pages : 0,
		errors: typeof value.errors === 'number' ? value.errors : 0,
		vendors: strings( value.vendors ),
		hosts: strings( value.hosts ),
	};
}

/**
 * Reads the schedule, the invalid values are skipped.
 * @param raw The schedule printed by the page or returned by the REST API.
 */
export function parseScanSchedule( raw: unknown ): ScanSchedule {
	const data = isRecord( raw ) ? raw : {};
	const frequency = FREQUENCIES.find( ( value ) => value === data.frequency );

	return {
		frequency: frequency ?? 'off',
		home: data.home === true,
		postTypes: strings( data.postTypes ),
		categories: terms( data.categories ),
		posts: terms( data.posts ),
		email: data.email === true,
		adminEmail: typeof data.adminEmail === 'string' ? data.adminEmail : '',
		report: reportFrom( data.report ),
	};
}
