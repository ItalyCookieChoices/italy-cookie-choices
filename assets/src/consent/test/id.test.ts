import { randomId } from '../id';

const UUID_V4 =
	/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;

describe( 'randomId', () => {
	it( 'uses crypto.randomUUID() when it exists', () => {
		const crypto = {
			randomUUID: () => '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
		} as unknown as Crypto;

		expect( randomId( crypto ) ).toBe(
			'3f2504e0-4f89-41d3-9a0c-0305e82c3301'
		);
	} );

	it( 'builds a version 4 UUID from random bytes without it', () => {
		const crypto = {
			getRandomValues: < T extends ArrayBufferView >( array: T ): T => {
				( array as unknown as Uint8Array ).fill( 0xff );
				return array;
			},
		} as unknown as Crypto;

		const id = randomId( crypto );

		expect( id ).toMatch( UUID_V4 );
		expect( id ).toBe( 'ffffffff-ffff-4fff-bfff-ffffffffffff' );
	} );
} );
