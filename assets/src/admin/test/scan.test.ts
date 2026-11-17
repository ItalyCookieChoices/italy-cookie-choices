import { batches, emptyReport, mergeScan } from '../scan';

it( 'merges the pages of the batches in a report', () => {
	const first = mergeScan( emptyReport(), [
		{
			id: 0,
			url: 'https://example.com/',
			vendors: [ 'youtube' ],
			hosts: [ 'cdn.example.org' ],
			error: '',
		},
	] );

	expect(
		mergeScan( first, [
			{
				id: 2,
				url: 'https://example.com/about/',
				vendors: [ 'youtube', 'hotjar' ],
				hosts: [],
				error: '',
			},
			{
				id: 3,
				url: 'https://example.com/broken/',
				vendors: [],
				hosts: [],
				error: 'The page answered with the status 500.',
			},
		] )
	).toEqual( {
		vendors: {
			youtube: [ 'https://example.com/', 'https://example.com/about/' ],
			hotjar: [ 'https://example.com/about/' ],
		},
		hosts: { 'cdn.example.org': [ 'https://example.com/' ] },
		errors: [
			{
				url: 'https://example.com/broken/',
				error: 'The page answered with the status 500.',
			},
		],
		scanned: 3,
	} );
} );

it( 'splits the targets in batches of ten', () => {
	const targets = Array.from( { length: 23 }, ( _, id ) => ( {
		id,
		title: `Page ${ id }`,
		url: `https://example.com/${ id }/`,
	} ) );

	expect( batches( targets ).map( ( batch ) => batch.length ) ).toEqual( [
		10, 10, 3,
	] );
} );
