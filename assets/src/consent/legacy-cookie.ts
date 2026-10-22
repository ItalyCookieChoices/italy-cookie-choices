import type { Cookies } from './cookies';
import type { Consent, ConsentApi, ConsentConfig } from './types';

const DAY = 24 * 60 * 60 * 1000;

/**
 * Keeps the 2.x cookie for the themes and the plugins that read it.
 *
 * The 2.x cookie is not split by category: it exists only while every category is accepted.
 * A visitor who accepted with the 2.x banner is asked again, that consent may come from a scroll.
 *
 * @param api     The consent API.
 * @param cookies The site cookies.
 * @param config  The consent configuration.
 */
export function syncLegacyCookie(
	api: ConsentApi,
	cookies: Cookies,
	config: ConsentConfig
): void {
	const legacyCookie = config.legacyCookie;
	if ( legacyCookie === null ) {
		return;
	}

	const optional = config.categories
		.filter( ( category ) => ! category.required )
		.map( ( category ) => category.id );
	const acceptsEverything = ( consent: Consent ): boolean =>
		optional.every( ( category ) =>
			consent.categories.includes( category )
		);

	const sync = ( consent: Consent | null ): void => {
		if ( consent === null || ! acceptsEverything( consent ) ) {
			cookies.remove( legacyCookie.name );
			return;
		}

		const expires = new Date(
			Date.parse( consent.date ) + config.durationDays * DAY
		);
		cookies.write( legacyCookie.name, legacyCookie.value, expires );
	};

	sync( api.getConsent() );
	api.onChange( ( { consent } ) => sync( consent ) );
}
