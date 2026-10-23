import apiFetch from '@wordpress/api-fetch';
import { normalizeSettings, type Settings } from './settings';

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

function isRecord( value: unknown ): value is Record< string, unknown > {
	return typeof value === 'object' && value !== null;
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
