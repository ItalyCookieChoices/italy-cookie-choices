/**
 * The data printed by ItalyCookieChoices\Settings\UI\NetworkSettingsPage.
 */
export interface Site {
	id: number;
	name: string;
	url: string;
}

export interface NetworkData {
	shared: boolean;
	mainSiteId: number;
	sites: Site[];
}

function isRecord( value: unknown ): value is Record< string, unknown > {
	return typeof value === 'object' && value !== null;
}

function isSite( value: unknown ): value is Site {
	return (
		isRecord( value ) &&
		typeof value.id === 'number' &&
		typeof value.name === 'string' &&
		typeof value.url === 'string'
	);
}

export function parseNetworkData( value: unknown ): NetworkData {
	const data = isRecord( value ) ? value : {};

	return {
		shared: data.shared === true,
		mainSiteId: typeof data.mainSiteId === 'number' ? data.mainSiteId : 0,
		sites: Array.isArray( data.sites ) ? data.sites.filter( isSite ) : [],
	};
}
