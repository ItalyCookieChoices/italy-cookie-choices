/**
 * Whether a value read from the page or from the REST API is an object with named keys, not a list.
 * @param value The value to check.
 */
export function isRecord( value: unknown ): value is Record< string, unknown > {
	return (
		typeof value === 'object' && value !== null && ! Array.isArray( value )
	);
}
