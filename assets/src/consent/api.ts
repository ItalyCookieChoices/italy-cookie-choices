import type { Cookies } from './cookies';
import {
	CONSENT_CHANGED,
	type Consent,
	type ConsentApi,
	type ConsentChange,
	type ConsentConfig,
	type ConsentListener,
} from './types';

const DAY = 24 * 60 * 60 * 1000;

function isConsent( value: unknown ): value is Consent {
	if ( typeof value !== 'object' || value === null ) {
		return false;
	}

	const consent = value as Record< string, unknown >;

	return (
		Array.isArray( consent.categories ) &&
		consent.categories.every(
			( category ) => typeof category === 'string'
		) &&
		typeof consent.version === 'string' &&
		typeof consent.date === 'string'
	);
}

/**
 * The consent API, the consent lives in a cookie that ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository reads too.
 * @param config  The consent configuration.
 * @param cookies Where the consent is stored.
 * @param events  Where the change events are dispatched, the document in the page.
 * @param now     The current date, replaceable in the tests.
 */
export function createConsentApi(
	config: ConsentConfig,
	cookies: Cookies,
	events: EventTarget,
	now: () => Date = () => new Date()
): ConsentApi {
	const categoryIds = config.categories.map( ( category ) => category.id );
	const requiredIds = config.categories
		.filter( ( category ) => category.required )
		.map( ( category ) => category.id );

	function storedConsent(): Consent | null {
		const value = cookies.read( config.cookieName );
		if ( value === null ) {
			return null;
		}

		try {
			const consent: unknown = JSON.parse( decodeURIComponent( value ) );
			return isConsent( consent ) ? consent : null;
		} catch {
			return null;
		}
	}

	function getConsent(): Consent | null {
		const consent = storedConsent();
		if ( consent === null || consent.version !== config.version ) {
			return null;
		}

		const givenAt = Date.parse( consent.date );
		if (
			Number.isNaN( givenAt ) ||
			givenAt + config.durationDays * DAY <= now().getTime()
		) {
			return null;
		}

		return { ...consent, categories: [ ...consent.categories ] };
	}

	function dispatch( change: ConsentChange ): void {
		events.dispatchEvent(
			new CustomEvent< ConsentChange >( CONSENT_CHANGED, {
				detail: change,
			} )
		);
	}

	function setConsent( accepted: string[] ): Consent {
		const chosen = new Set( [ ...requiredIds, ...accepted ] );
		const previous = getConsent();
		const givenAt = now();
		const consent: Consent = {
			categories: categoryIds.filter( ( category ) =>
				chosen.has( category )
			),
			version: config.version,
			date: givenAt.toISOString(),
		};

		cookies.write(
			config.cookieName,
			encodeURIComponent( JSON.stringify( consent ) ),
			new Date( givenAt.getTime() + config.durationDays * DAY )
		);
		dispatch( { consent, previous } );

		return consent;
	}

	return {
		categories: () =>
			config.categories.map( ( category ) => ( { ...category } ) ),
		getConsent,
		hasConsent( category: string ): boolean {
			if ( requiredIds.includes( category ) ) {
				return true;
			}

			return getConsent()?.categories.includes( category ) ?? false;
		},
		setConsent,
		acceptAll: () => setConsent( categoryIds ),
		rejectAll: () => setConsent( [] ),
		withdraw(): void {
			const previous = getConsent();
			cookies.remove( config.cookieName );
			dispatch( { consent: null, previous } );
		},
		onChange( listener: ConsentListener ): () => void {
			const handler = ( event: Event ): void => {
				const { consent, previous } = (
					event as CustomEvent< ConsentChange >
				 ).detail;
				listener( consent, previous );
			};
			events.addEventListener( CONSENT_CHANGED, handler );

			return () => events.removeEventListener( CONSENT_CHANGED, handler );
		},
	};
}
