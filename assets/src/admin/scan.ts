/**
 * The scan of the site, see the Scan context in src/Scan: the pages are scanned in batches,
 * the results of the batches are merged in a report.
 */

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
