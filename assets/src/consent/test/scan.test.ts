import { createScanApi } from '../scan';
import type { ConsentConfig } from '../types';

const config: ConsentConfig = {
	cookieName: 'icc_consent',
	categories: [
		{ id: 'necessary', required: true },
		{ id: 'statistics', required: false },
		{ id: 'marketing', required: false },
	],
	version: '1',
	durationDays: 10,
	legacyCookie: null,
	vendorConsent: true,
	vendors: [ { id: 'youtube', category: 'marketing' } ],
	scan: true,
};

describe( 'createScanApi', () => {
	it( 'accepts every category and vendor', () => {
		const api = createScanApi( config, new EventTarget() );

		expect( api.getConsent()?.categories ).toEqual( [
			'necessary',
			'statistics',
			'marketing',
		] );
		expect( api.hasVendorConsent( 'youtube', 'marketing' ) ).toBe( true );
	} );

	it( 'writes no consent cookie', () => {
		createScanApi( config, new EventTarget() );

		expect( document.cookie ).not.toContain( 'icc_consent' );
	} );
} );
