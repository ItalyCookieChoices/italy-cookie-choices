/**
 * The REST requests of the network page, the routes check the manage_network_options capability.
 */
import apiFetch from '@wordpress/api-fetch';

function isRecord( value: unknown ): value is Record< string, unknown > {
	return typeof value === 'object' && value !== null;
}

export async function saveNetwork(
	shared: boolean
): Promise< { shared: boolean } > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/network',
		method: 'POST',
		data: { shared },
	} );

	return { shared: isRecord( response ) && response.shared === true };
}

/**
 * Copies the settings of a site to other sites, the ids of the sites that got them.
 * @param source  The site to copy from.
 * @param targets The sites to copy to.
 * @param texts   Whether the texts are copied too.
 */
export async function copySettings(
	source: number,
	targets: number[],
	texts: boolean
): Promise< number[] > {
	const response: unknown = await apiFetch( {
		path: '/italy-cookie-choices/v1/network/copy',
		method: 'POST',
		data: { source, targets, texts },
	} );

	return isRecord( response ) && Array.isArray( response.copied )
		? response.copied.filter(
				( id ): id is number => typeof id === 'number'
			)
		: [];
}
