import {
	durationText,
	emptyCookieReport,
	matchesCookie,
	ownerOf,
	withPageCookies,
	withPageError,
} from '../cookie-scan';

const HOUR = 3600;
const DAY = 24 * HOUR;

describe( 'withPageCookies', () => {
	it( 'merges the cookies of the pages by name, with their pages', () => {
		const ga = {
			name: '_ga',
			path: '/',
			duration: 730 * DAY,
			httpOnly: false,
		};
		const lang = {
			name: 'lang',
			path: '/',
			duration: null,
			httpOnly: false,
		};

		let report = withPageCookies( emptyCookieReport(), '/a', [ ga ] );
		report = withPageCookies( report, '/b', [ ga, lang ] );

		expect( report ).toEqual( {
			cookies: {
				_ga: { ...ga, pages: [ '/a', '/b' ] },
				lang: { ...lang, pages: [ '/b' ] },
			},
			errors: [],
			scanned: 2,
		} );
	} );

	it( 'keeps the errors of the pages', () => {
		expect( withPageError( emptyCookieReport(), '/a', 'refused' ) ).toEqual(
			{
				cookies: {},
				errors: [ { url: '/a', error: 'refused' } ],
				scanned: 1,
			}
		);
	} );
} );

describe( 'matchesCookie', () => {
	it.each( [
		[ '_ga', '_ga', true ],
		[ '_ga', '_gat', false ],
		[ '_ga_*', '_ga_ABC123', true ],
		[ '_pk_id.*', '_pk_id.1.abcd', true ],
		[ '_ga_*', '_ga', false ],
	] )( 'matches %s with %s: %s', ( known, name, matches ) => {
		expect( matchesCookie( known, name ) ).toBe( matches );
	} );
} );

describe( 'ownerOf', () => {
	const vendors = [
		{
			id: 'google-analytics',
			name: 'Google Analytics',
			category: 'statistics',
			cookies: [ '_ga', '_ga_*' ],
		},
	];
	const customVendors = [
		{
			id: 'custom-1',
			name: 'Tracker',
			category: 'marketing',
			patterns: [ 'tracker.example' ],
			cookies: [ { name: 'trk_*', duration: '', purpose: '' } ],
		},
	];
	const siteCookies = [
		{ name: 'wp_lang', category: 'necessary', duration: '', purpose: '' },
	];

	it( 'finds the vendor of a cookie', () => {
		expect(
			ownerOf( '_ga_X1', vendors, customVendors, siteCookies )
		).toEqual( {
			kind: 'vendor',
			name: 'Google Analytics',
		} );
		expect(
			ownerOf( 'trk_id', vendors, customVendors, siteCookies )
		).toEqual( {
			kind: 'vendor',
			name: 'Tracker',
		} );
	} );

	it( 'finds a site cookie', () => {
		expect(
			ownerOf( 'wp_lang', vendors, customVendors, siteCookies )
		).toEqual( {
			kind: 'site',
		} );
	} );

	it( 'does not know the other cookies', () => {
		expect(
			ownerOf(
				'woocommerce_cart_hash',
				vendors,
				customVendors,
				siteCookies
			)
		).toEqual( {
			kind: 'unknown',
		} );
	} );
} );

describe( 'durationText', () => {
	it.each( [
		[ null, 'Session' ],
		[ 10, '1 minute' ],
		[ 30 * 60, '30 minutes' ],
		[ 2 * HOUR, '2 hours' ],
		[ DAY, '1 day' ],
		[ 30 * DAY, '30 days' ],
		[ 90 * DAY, '3 months' ],
		[ 182 * DAY, '6 months' ],
		[ 365 * DAY, '1 year' ],
		[ 400 * DAY, '13 months' ],
		[ 730 * DAY, '2 years' ],
	] )( 'writes %s seconds as %s', ( seconds, text ) => {
		expect( durationText( seconds ) ).toBe( text );
	} );
} );
