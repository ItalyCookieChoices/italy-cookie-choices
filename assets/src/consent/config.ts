import type { Category, ConsentConfig, LegacyCookie } from './types';

const DEFAULTS: ConsentConfig = {
	cookieName: 'icc_consent',
	categories: [],
	version: '1',
	durationDays: 182,
	legacyCookie: null,
};

function isRecord( value: unknown ): value is Record< string, unknown > {
	return typeof value === 'object' && value !== null;
}

function categoryFrom( value: unknown ): Category | null {
	if (
		! isRecord( value ) ||
		typeof value.id !== 'string' ||
		value.id === ''
	) {
		return null;
	}

	return { id: value.id, required: value.required === true };
}

function legacyCookieFrom( value: unknown ): LegacyCookie | null {
	if (
		! isRecord( value ) ||
		typeof value.name !== 'string' ||
		typeof value.value !== 'string' ||
		value.name === ''
	) {
		return null;
	}

	return { name: value.name, value: value.value };
}

/**
 * Reads the configuration printed in the page, the invalid values fall back to the defaults.
 * @param raw The value of window.italyCookieChoicesConfig.
 */
export function parseConfig( raw: unknown ): ConsentConfig {
	if ( ! isRecord( raw ) ) {
		return DEFAULTS;
	}

	const categories = Array.isArray( raw.categories )
		? raw.categories
				.map( categoryFrom )
				.filter(
					( category ): category is Category => category !== null
				)
		: DEFAULTS.categories;

	return {
		cookieName:
			typeof raw.cookieName === 'string' && raw.cookieName !== ''
				? raw.cookieName
				: DEFAULTS.cookieName,
		categories,
		version:
			typeof raw.version === 'string' && raw.version !== ''
				? raw.version
				: DEFAULTS.version,
		durationDays:
			typeof raw.durationDays === 'number' && raw.durationDays > 0
				? raw.durationDays
				: DEFAULTS.durationDays,
		legacyCookie: legacyCookieFrom( raw.legacyCookie ),
	};
}
