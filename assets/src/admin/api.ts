import apiFetch from '@wordpress/api-fetch';
import {
	parseScanSchedule,
	type Frequency,
	type ScannedPage,
	type ScanSchedule,
	type ScanSelection,
	type ScanTarget,
	type Term,
} from './scan';

export type { Term } from './scan';
import { parseSuggestions, type ScanSuggestions } from './assistant';
import {
	normalizeSettings,
	linkTranslationRowsFrom,
	type BannerLink,
	type LinkTranslation,
	type Settings,
} from './settings';
import { isRecord } from '../shared/is-record';

/**
 * The REST requests of the settings page, the routes check the manage_options capability.
 */
const OPTION = 'italy_cookie_choices';

export type Dialog = 'banner' | 'preferences';

export interface Page {
	slug: string;
	link: string;
	title: string;
}

export async function loadSettings(): Promise< Settings > {
	const response: unknown = await apiFetch( { path: '/wp/v2/settings' } );

	return normalizeSettings(
		isRecord( response ) ? response[ OPTION ] : null
	);
}

export async function saveSettings( settings: Settings ): Promise< Settings > {
	const response: unknown = await apiFetch( {
		path: '/wp/v2/settings',
		method: 'POST',
		data: { [ OPTION ]: settings },
	} );

	return normalizeSettings(
		isRecord( response ) ? response[ OPTION ] : null
	);
}

/**
 * Saves the settings of an exported file, migrated from the version of the file.
 * @param file The parsed JSON of the file.
 */
export async function importSettings( file: unknown ): Promise< Settings > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/settings/import',
		method: 'POST',
		data: { file },
	} );

	return normalizeSettings( response );
}

/** What the import of another plugin changes, and what to check after it. */
export interface MigrationResult {
	summary: string[];
	notes: string[];
}

function strings( value: unknown ): string[] {
	return Array.isArray( value )
		? value.filter( ( item ): item is string => typeof item === 'string' )
		: [];
}

function migrationResultFrom( response: unknown ): MigrationResult {
	const data = isRecord( response ) ? response : {};

	return { summary: strings( data.summary ), notes: strings( data.notes ) };
}

/**
 * What the import of the settings of another plugin changes, nothing is saved.
 * @param source The id of the plugin, like complianz.
 */
export async function previewMigration(
	source: string
): Promise< MigrationResult > {
	return migrationResultFrom(
		await apiFetch( {
			path: `/italy-cookie-choices/v1/migration/${ source }`,
		} )
	);
}

/**
 * Imports the settings of another plugin, the saved settings come back.
 * @param source The id of the plugin, like complianz.
 */
export async function importMigration(
	source: string
): Promise< MigrationResult & { settings: Settings } > {
	const response: unknown = await apiFetch( {
		path: `/italy-cookie-choices/v1/migration/${ source }`,
		method: 'POST',
	} );

	return {
		...migrationResultFrom( response ),
		settings: normalizeSettings(
			isRecord( response ) ? response.settings : null
		),
	};
}

/**
 * The page that shows the banner of the settings not saved yet.
 * @param settings The settings to preview.
 * @param dialog   The dialog to show.
 */
export async function previewHtml(
	settings: Settings,
	dialog: Dialog
): Promise< string > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/preview',
		method: 'POST',
		data: { settings, dialog },
	} );

	return isRecord( response ) && typeof response.html === 'string'
		? response.html
		: '';
}

/**
 * The text of a rendered title, like "Cookie &#8211; policy".
 * @param html The rendered title.
 */
function textFrom( html: string ): string {
	return (
		new DOMParser().parseFromString( html, 'text/html' ).documentElement
			.textContent ?? ''
	);
}

function pageFrom( value: unknown ): Page | null {
	if (
		! isRecord( value ) ||
		typeof value.slug !== 'string' ||
		typeof value.link !== 'string'
	) {
		return null;
	}

	const title =
		isRecord( value.title ) && typeof value.title.rendered === 'string'
			? textFrom( value.title.rendered )
			: '';

	return {
		slug: value.slug,
		link: value.link,
		title: title !== '' ? title : value.slug,
	};
}

/**
 * The pages found by their title, only when the site owner types (#154).
 * @param search The text typed.
 */
export async function searchPages( search: string ): Promise< Page[] > {
	const query = new URLSearchParams( {
		search,
		per_page: '20',
		_fields: 'slug,link,title',
	} );
	const response: unknown = await apiFetch( {
		path: `/wp/v2/pages?${ query.toString() }`,
	} );

	return Array.isArray( response )
		? response
				.map( pageFrom )
				.filter( ( page ): page is Page => page !== null )
		: [];
}

/** An image of the media library, as the settings page shows it. */
export interface Media {
	url: string;
	alt: string;
}

/**
 * The address and the alternative text of an attachment, null when it is missing.
 * @param id The attachment id.
 */
export async function loadMedia( id: number ): Promise< Media | null > {
	const response: unknown = await apiFetch( {
		path: `/wp/v2/media/${ id }?_fields=source_url,alt_text`,
	} );
	if ( ! isRecord( response ) || typeof response.source_url !== 'string' ) {
		return null;
	}

	return {
		url: response.source_url,
		alt: typeof response.alt_text === 'string' ? response.alt_text : '',
	};
}

/** The choices of a month of the consent log. */
export interface MonthStatistics {
	month: string;
	choices: number;
	acceptAll: number;
	rejectAll: number;
	partial: number;
	withdrawals: number;
	/** The choices that accepted each optional category, keyed by category id. */
	categories: Record< string, number >;
}

export interface Statistics {
	categories: Array< { id: string; label: string } >;
	months: MonthStatistics[];
}

function count( value: unknown ): number {
	return typeof value === 'number' ? value : 0;
}

function monthFrom( value: unknown ): MonthStatistics | null {
	if ( ! isRecord( value ) || typeof value.month !== 'string' ) {
		return null;
	}

	const categories: Record< string, number > = {};
	if ( isRecord( value.categories ) ) {
		for ( const [ id, choices ] of Object.entries( value.categories ) ) {
			categories[ id ] = count( choices );
		}
	}

	return {
		month: value.month,
		choices: count( value.choices ),
		acceptAll: count( value.acceptAll ),
		rejectAll: count( value.rejectAll ),
		partial: count( value.partial ),
		withdrawals: count( value.withdrawals ),
		categories,
	};
}

/**
 * The choices of the consent log by month, from GET /italy-cookie-choices/v1/statistics.
 */
export async function loadStatistics(): Promise< Statistics > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/statistics',
	} );
	if ( ! isRecord( response ) ) {
		return { categories: [], months: [] };
	}

	const categories = Array.isArray( response.categories )
		? response.categories.filter(
				( category ): category is { id: string; label: string } =>
					isRecord( category ) &&
					typeof category.id === 'string' &&
					typeof category.label === 'string'
			)
		: [];
	const months = Array.isArray( response.months )
		? response.months
				.map( monthFrom )
				.filter( ( month ): month is MonthStatistics => month !== null )
		: [];

	return { categories, months };
}

/**
 * The message of a failed request.
 * @param error    What apiFetch rejected.
 * @param fallback The message when the error has none.
 */
export function errorMessage( error: unknown, fallback: string ): string {
	return isRecord( error ) &&
		typeof error.message === 'string' &&
		error.message !== ''
		? error.message
		: fallback;
}

export interface SiteTexts {
	texts: Settings;
	/** The translation of the policy page of a connected site, empty when there is none. */
	policySuggestion: string;
}

function siteTextsFrom( response: unknown ): SiteTexts {
	const data = isRecord( response ) ? response : {};

	return {
		texts: normalizeSettings( data.texts ),
		policySuggestion:
			typeof data.policySuggestion === 'string'
				? data.policySuggestion
				: '',
	};
}

/**
 * The texts of this site or of a connected site, the route checks that the user administers it.
 * @param siteId The site.
 */
export async function loadSiteTexts( siteId: number ): Promise< SiteTexts > {
	return siteTextsFrom(
		await apiFetch( {
			path: `/italy-cookie-choices/v1/sites/${ siteId }/texts`,
		} )
	);
}

/**
 * Saves the texts of a connected site, its other settings stay.
 * @param siteId The site.
 * @param texts  The texts.
 */
export async function saveSiteTexts(
	siteId: number,
	texts: Settings
): Promise< SiteTexts > {
	return siteTextsFrom(
		await apiFetch( {
			path: `/italy-cookie-choices/v1/sites/${ siteId }/texts`,
			method: 'POST',
			data: { texts },
		} )
	);
}

/**
 * The texts of a site with the empty ones filled from a connected site, not saved.
 * @param siteId    The site to fill.
 * @param source    The connected site the texts come from.
 * @param translate Whether the texts are translated in the language of the site.
 */
export async function draftSiteTexts(
	siteId: number,
	source: number,
	translate: boolean
): Promise< SiteTexts > {
	return siteTextsFrom(
		await apiFetch( {
			path: `/italy-cookie-choices/v1/sites/${ siteId }/texts/draft`,
			method: 'POST',
			data: { source, translate },
		} )
	);
}

function termsFrom( response: unknown, name: string ): Term[] {
	return Array.isArray( response )
		? response
				.filter( isRecord )
				.filter( ( term ) => typeof term.id === 'number' )
				.map( ( term ) => ( {
					id: Number( term.id ),
					name:
						typeof term[ name ] === 'string'
							? String( term[ name ] )
							: '',
				} ) )
		: [];
}

/**
 * The categories whose name contains the search.
 * @param search The text typed by the site owner.
 */
export async function searchCategories( search: string ): Promise< Term[] > {
	return termsFrom(
		await apiFetch( {
			path: `/wp/v2/categories?per_page=20&_fields=id,name&search=${ encodeURIComponent(
				search
			) }`,
		} ),
		'name'
	);
}

/**
 * The posts and pages whose title contains the search.
 * @param search The text typed by the site owner.
 */
export async function searchPosts( search: string ): Promise< Term[] > {
	return termsFrom(
		await apiFetch( {
			path: `/wp/v2/search?type=post&per_page=20&search=${ encodeURIComponent(
				search
			) }`,
		} ),
		'title'
	);
}

/**
 * The published pages of a selection, the home page has the id 0.
 * @param selection What the site owner chose to scan.
 */
export async function scanTargets(
	selection: ScanSelection
): Promise< { targets: ScanTarget[]; truncated: boolean } > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/scan/targets',
		method: 'POST',
		data: selection,
	} );
	const data = isRecord( response ) ? response : {};

	return {
		targets: Array.isArray( data.targets )
			? ( data.targets.filter( isRecord ) as unknown as ScanTarget[] )
			: [],
		truncated: data.truncated === true,
	};
}

/**
 * Scans a batch of pages from the server.
 * @param ids The pages of the batch.
 */
export async function scanPages( ids: number[] ): Promise< ScannedPage[] > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/scan/pages',
		method: 'POST',
		data: { ids },
	} );
	const data = isRecord( response ) ? response : {};

	return Array.isArray( data.pages )
		? ( data.pages.filter( isRecord ) as unknown as ScannedPage[] )
		: [];
}

/**
 * The address of a page of the site in scan mode, with a one-time token, for the scan of the cookies.
 * @param url The address of the page.
 */
export async function frameScanUrl( url: string ): Promise< string > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/scan/frame',
		method: 'POST',
		data: { url },
	} );

	return isRecord( response ) && typeof response.url === 'string'
		? response.url
		: '';
}

/**
 * The suggestions of the AI provider of the site for the unknown third parties and cookies.
 * @param hosts   The unknown third parties.
 * @param cookies The names of the unknown cookies.
 */
export async function suggestForScan(
	hosts: readonly string[],
	cookies: readonly string[]
): Promise< ScanSuggestions > {
	return parseSuggestions(
		await apiFetch( {
			path: '/italy-cookie-choices/v1/scan/suggestions',
			method: 'POST',
			data: { hosts, cookies },
		} )
	);
}

/**
 * Saves the schedule of the scan, the server schedules its cron event.
 * @param schedule The frequency, the selection and the email choice.
 */
export async function saveScanSchedule(
	schedule: ScanSelection & { frequency: Frequency; email: boolean }
): Promise< ScanSchedule > {
	return parseScanSchedule(
		await apiFetch( {
			path: '/italy-cookie-choices/v1/scan/schedule',
			method: 'POST',
			data: schedule,
		} )
	);
}

/**
 * Uses the translation of the page of a link as the link of a language, saved in WPML or Polylang.
 * @param link     The link of the banner.
 * @param language The code of the language.
 */
export async function saveLinkTranslation(
	link: BannerLink,
	language: string
): Promise< LinkTranslation[] > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/link-translations',
		method: 'POST',
		data: { language, link },
	} );

	return linkTranslationRowsFrom(
		isRecord( response ) ? response.rows : null
	);
}
