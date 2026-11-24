import { isRecord } from '../is-record';

describe( 'isRecord', () => {
	it( 'accepts an object with named keys', () => {
		expect( isRecord( { id: 'necessary' } ) ).toBe( true );
		expect( isRecord( {} ) ).toBe( true );
	} );

	it.each( [
		[ null ],
		[ undefined ],
		[ 'text' ],
		[ 1 ],
		[ [] ],
		[ [ 1 ] ],
	] )( 'refuses %p', ( value ) => {
		expect( isRecord( value ) ).toBe( false );
	} );
} );
