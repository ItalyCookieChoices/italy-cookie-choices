import { parseNetworkData } from '../data';

it( 'reads the data printed by the network page', () => {
	expect(
		parseNetworkData( {
			shared: true,
			mainSiteId: 1,
			sites: [
				{ id: 1, name: 'Main', url: 'https://example.com/' },
				{ id: '2', name: 'Broken' },
			],
		} )
	).toEqual( {
		shared: true,
		mainSiteId: 1,
		sites: [ { id: 1, name: 'Main', url: 'https://example.com/' } ],
	} );
} );

it( 'has no sites without the data', () => {
	expect( parseNetworkData( undefined ) ).toEqual( {
		shared: false,
		mainSiteId: 0,
		sites: [],
	} );
} );
