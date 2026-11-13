import type { Settings } from './settings';

/**
 * The texts of the languages tab, see the Multilingual context in src/Multilingual.
 */
type Texts = Record< string, unknown >;

function isRecord( value: unknown ): value is Record< string, unknown > {
	return (
		typeof value === 'object' && value !== null && ! Array.isArray( value )
	);
}

function isEmpty( value: unknown ): boolean {
	return (
		value === undefined ||
		value === '' ||
		( isRecord( value ) && Object.keys( value ).length === 0 ) ||
		( Array.isArray( value ) && value.length === 0 )
	);
}

function filledRecord( own: unknown, draft: unknown ): Texts {
	const filled: Texts = isRecord( own ) ? { ...own } : {};
	if ( ! isRecord( draft ) ) {
		return filled;
	}

	for ( const [ key, value ] of Object.entries( draft ) ) {
		filled[ key ] = filledValue( filled[ key ], value );
	}

	return filled;
}

function filledValue( own: unknown, draft: unknown ): unknown {
	if ( isRecord( draft ) ) {
		return filledRecord( own, draft );
	}

	return isEmpty( own ) ? draft : own;
}

/**
 * The settings with the empty texts filled from a draft: the texts written in the form, saved or not, stay.
 * @param settings The settings of the form.
 * @param draft    The texts filled from a connected site.
 */
export function fillEmptyTexts(
	settings: Settings,
	draft: Settings
): Settings {
	return filledRecord( settings, draft ) as Settings;
}
