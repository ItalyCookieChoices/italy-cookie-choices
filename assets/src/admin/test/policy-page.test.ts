import { pageOptions } from '../policy-page';

const page = {
	slug: 'cookie-policy',
	link: 'https://example.com/cookie-policy/',
	title: 'Cookie policy',
};

describe( 'pageOptions', () => {
	it( 'lists the found pages', () => {
		expect( pageOptions( [ page ], 'cookie-policy' ) ).toEqual( [
			{ value: 'cookie-policy', label: 'Cookie policy' },
		] );
	} );

	it( 'keeps the saved page that was not found', () => {
		expect( pageOptions( [ page ], 'privacy' ) ).toEqual( [
			{ value: 'privacy', label: 'privacy' },
			{ value: 'cookie-policy', label: 'Cookie policy' },
		] );
	} );

	it( 'has no saved page when the slug is empty', () => {
		expect( pageOptions( [], '' ) ).toEqual( [] );
	} );
} );
