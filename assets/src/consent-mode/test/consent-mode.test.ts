import { Cookies } from '../../consent/cookies';
import { CONSENT_CHANGED } from '../../consent/types';
import {
	consentState,
	parseMapping,
	startConsentMode,
	type ConsentModeConfig,
} from '../consent-mode';

const config: ConsentModeConfig = {
	consent: {
		cookieName: 'icc_consent',
		categories: [
			{ id: 'necessary', required: true },
			{ id: 'statistics', required: false },
			{ id: 'marketing', required: false },
		],
		version: '1',
		durationDays: 10,
		legacyCookie: null,
	},
	mapping: {
		necessary: [ 'security_storage' ],
		statistics: [ 'analytics_storage' ],
		marketing: [ 'ad_storage', 'ad_user_data' ],
	},
	waitForUpdate: 500,
};

const DENIED = {
	security_storage: 'granted',
	analytics_storage: 'denied',
	ad_storage: 'denied',
	ad_user_data: 'denied',
};

function storeConsent( categories: string[], version = '1' ): void {
	const consent = {
		id: '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
		categories,
		version,
		date: new Date().toISOString(),
	};
	document.cookie = `icc_consent=${ encodeURIComponent( JSON.stringify( consent ) ) }; path=/`;
}

function start(): jest.Mock {
	const gtag = jest.fn();
	startConsentMode( config, gtag, new Cookies( document, false ), document );

	return gtag;
}

function change( categories: string[] | null ): void {
	document.dispatchEvent(
		new CustomEvent( CONSENT_CHANGED, {
			detail: {
				consent:
					categories === null
						? null
						: { id: 'a', categories, version: '1', date: '' },
				previous: null,
				fromAnotherTab: false,
			},
		} )
	);
}

afterEach( () => {
	document.cookie = `icc_consent=; expires=${ new Date( 0 ).toUTCString() }; path=/`;
} );

describe( 'consentState', () => {
	it( 'grants the types of the accepted categories', () => {
		expect(
			consentState( config.mapping, [ 'necessary', 'marketing' ] )
		).toEqual( {
			security_storage: 'granted',
			analytics_storage: 'denied',
			ad_storage: 'granted',
			ad_user_data: 'granted',
		} );
	} );

	it( 'ignores the categories without types', () => {
		expect( consentState( config.mapping, [ 'social' ] ) ).toEqual( {
			...DENIED,
			security_storage: 'denied',
		} );
	} );
} );

describe( 'parseMapping', () => {
	it( 'keeps the lists of strings', () => {
		expect(
			parseMapping( {
				statistics: [ 'analytics_storage', 42 ],
				marketing: 'ad_storage',
			} )
		).toEqual( { statistics: [ 'analytics_storage' ] } );
		expect( parseMapping( null ) ).toEqual( {} );
	} );
} );

describe( 'startConsentMode', () => {
	it( 'denies everything but the required categories by default', () => {
		const gtag = start();

		expect( gtag.mock.calls ).toEqual( [
			[ 'consent', 'default', { ...DENIED, wait_for_update: 500 } ],
			[ 'set', 'ads_data_redaction', true ],
		] );
	} );

	it( 'updates the state with the stored consent', () => {
		storeConsent( [ 'necessary', 'statistics' ] );

		const gtag = start();

		expect( gtag ).toHaveBeenLastCalledWith( 'consent', 'update', {
			...DENIED,
			analytics_storage: 'granted',
		} );
	} );

	it( 'ignores a consent given for another policy version', () => {
		storeConsent( [ 'necessary', 'statistics' ], '0' );

		expect( start() ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'updates the state on each change', () => {
		const gtag = start();

		change( [ 'necessary', 'marketing' ] );
		expect( gtag ).toHaveBeenLastCalledWith( 'consent', 'update', {
			...DENIED,
			ad_storage: 'granted',
			ad_user_data: 'granted',
		} );

		change( null );
		expect( gtag ).toHaveBeenLastCalledWith( 'consent', 'update', DENIED );
	} );
} );
