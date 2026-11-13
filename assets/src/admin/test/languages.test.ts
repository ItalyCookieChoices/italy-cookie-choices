import { fillEmptyTexts } from '../languages';
import { parseAdminData } from '../settings';

it( 'fills only the empty texts of the form', () => {
	expect(
		fillEmptyTexts(
			{
				text: '',
				button_text: 'Accetta',
				banner: '2',
				categories: { statistics: { label: 'Statistiche' } },
			},
			{
				text: 'We use cookies',
				button_text: 'Accept',
				categories: {
					statistics: { label: 'Statistics', description: 'Visits' },
					marketing: { label: 'Marketing' },
				},
			}
		)
	).toEqual( {
		text: 'We use cookies',
		button_text: 'Accetta',
		banner: '2',
		categories: {
			statistics: { label: 'Statistiche', description: 'Visits' },
			marketing: { label: 'Marketing' },
		},
	} );
} );

it( 'reads the connected sites printed by the page', () => {
	const data = parseAdminData( {
		languages: {
			siteId: 1,
			language: 'it-IT',
			sites: [
				{
					id: 2,
					name: 'Deutsch',
					url: 'https://example.com/de/',
					language: 'de-DE',
					canTranslate: true,
				},
				{ id: '3' },
			],
		},
	} );

	expect( data.languages ).toEqual( {
		siteId: 1,
		language: 'it-IT',
		sites: [
			{
				id: 2,
				name: 'Deutsch',
				url: 'https://example.com/de/',
				language: 'de-DE',
				canTranslate: true,
			},
		],
	} );
	expect( parseAdminData( {} ).languages ).toEqual( {
		siteId: 0,
		language: '',
		sites: [],
	} );
} );
