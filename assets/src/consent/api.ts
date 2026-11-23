import type { CookieJar } from './cookies';
import { randomId } from './id';
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
		typeof consent.id === 'string' &&
		consent.id !== '' &&
		Array.isArray( consent.categories ) &&
		consent.categories.every(
			( category ) => typeof category === 'string'
		) &&
		typeof consent.version === 'string' &&
		typeof consent.date === 'string' &&
		( consent.refusedVendors === undefined ||
			( Array.isArray( consent.refusedVendors ) &&
				consent.refusedVendors.every(
					( vendor ) => typeof vendor === 'string'
				) ) )
	);
}

/**
 * The consent API, the consent lives in a cookie that ItalyCookieChoices\Consent\Infrastructure\CookieConsentRepository reads too.
 * @param config  The consent configuration.
 * @param cookies Where the consent is stored.
 * @param events  Where the change events are dispatched, the document in the page.
 * @param now     The current date, replaceable in the tests.
 * @param newId   Creates the id of a new consent, replaceable in the tests.
 */
export function createConsentApi(
	config: ConsentConfig,
	cookies: CookieJar,
	events: EventTarget,
	now: () => Date = () => new Date(),
	newId: () => string = () => randomId()
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

		return {
			...consent,
			categories: [ ...consent.categories ],
			...( consent.refusedVendors === undefined
				? {}
				: { refusedVendors: [ ...consent.refusedVendors ] } ),
		};
	}

	function dispatch( change: ConsentChange ): void {
		events.dispatchEvent(
			new CustomEvent< ConsentChange >( CONSENT_CHANGED, {
				detail: change,
			} )
		);
	}

	const vendors =
		config.vendorConsent === true ? ( config.vendors ?? [] ) : [];

	/**
	 * The refused vendors that belong to an accepted category, the others are refused with their category.
	 * @param categories The accepted categories.
	 * @param refused    The vendors the visitor refused.
	 */
	function refusedOf( categories: string[], refused: string[] ): string[] {
		return vendors
			.filter(
				( vendor ) =>
					refused.includes( vendor.id ) &&
					categories.includes( vendor.category )
			)
			.map( ( vendor ) => vendor.id );
	}

	function setConsent( accepted: string[], refused: string[] = [] ): Consent {
		const chosen = new Set( [ ...requiredIds, ...accepted ] );
		const previous = getConsent();
		const givenAt = now();
		const categories = categoryIds.filter( ( category ) =>
			chosen.has( category )
		);
		const consent: Consent = {
			// The same browser keeps its id, also for another policy version.
			id: storedConsent()?.id ?? newId(),
			categories,
			version: config.version,
			date: givenAt.toISOString(),
			...( config.vendorConsent === true
				? { refusedVendors: refusedOf( categories, refused ) }
				: {} ),
		};

		cookies.write(
			config.cookieName,
			encodeURIComponent( JSON.stringify( consent ) ),
			new Date( givenAt.getTime() + config.durationDays * DAY )
		);
		dispatch( { consent, previous, fromAnotherTab: false } );

		return consent;
	}

	function hasConsent( category: string ): boolean {
		if ( requiredIds.includes( category ) ) {
			return true;
		}

		return getConsent()?.categories.includes( category ) ?? false;
	}

	return {
		categories: () =>
			config.categories.map( ( category ) => ( { ...category } ) ),
		getConsent,
		hasConsent,
		hasVendorConsent( vendor: string, category: string ): boolean {
			if ( ! hasConsent( category ) ) {
				return false;
			}

			return (
				config.vendorConsent !== true ||
				! ( getConsent()?.refusedVendors ?? [] ).includes( vendor )
			);
		},
		setConsent,
		acceptVendor( vendor: string, category: string ): Consent {
			const consent = getConsent();
			const accepted = consent?.categories ?? [];
			// The other vendors of a category accepted now stay refused, the refusals of the others stay.
			const others = accepted.includes( category )
				? []
				: vendors
						.filter( ( other ) => other.category === category )
						.map( ( other ) => other.id );
			const refused = [ ...( consent?.refusedVendors ?? [] ), ...others ];

			return setConsent(
				[ ...accepted, category ],
				refused.filter( ( id ) => id !== vendor )
			);
		},

		acceptAll: () => setConsent( categoryIds ),
		rejectAll: () => setConsent( [] ),
		withdraw(): void {
			const previous = getConsent();
			cookies.remove( config.cookieName );
			dispatch( { consent: null, previous, fromAnotherTab: false } );
		},
		onChange( listener: ConsentListener ): () => void {
			const handler = ( event: Event ): void => {
				listener( ( event as CustomEvent< ConsentChange > ).detail );
			};
			events.addEventListener( CONSENT_CHANGED, handler );

			return () => events.removeEventListener( CONSENT_CHANGED, handler );
		},
	};
}
