import {
	isChecked,
	isVendorBlocked,
	newCustomVendor,
	normalizeSettings,
	parseAdminData,
	withCategoryText,
	withCheckbox,
	withCustomVendor,
	withVendorBlocked,
	type CustomVendor,
} from '../settings';

const vendor: CustomVendor = {
	id: 'custom',
	name: 'Custom',
	category: 'marketing',
	patterns: [ 'tracker.example.com' ],
};

describe( 'parseAdminData', () => {
	it( 'reads the data printed in the page', () => {
		expect(
			parseAdminData( {
				categories: [
					{
						id: 'necessary',
						required: true,
						label: 'Necessary',
						description: 'Always.',
					},
					{ id: 'marketing' },
					{ label: 'No id' },
				],
				vendors: [
					{ id: 'youtube', name: 'YouTube', category: 'marketing' },
					{ id: 'x' },
				],
				consentLogExportUrl: 'https://example.com/export',
				documentationUrl: 42,
			} )
		).toEqual( {
			categories: [
				{
					id: 'necessary',
					required: true,
					label: 'Necessary',
					description: 'Always.',
				},
				{
					id: 'marketing',
					required: false,
					label: 'marketing',
					description: '',
				},
			],
			vendors: [
				{ id: 'youtube', name: 'YouTube', category: 'marketing' },
			],
			consentLogExportUrl: 'https://example.com/export',
			documentationUrl: '',
		} );
	} );

	it( 'returns empty data for an invalid value', () => {
		expect( parseAdminData( undefined ) ).toEqual( {
			categories: [],
			vendors: [],
			consentLogExportUrl: '',
			documentationUrl: '',
		} );
	} );
} );

describe( 'normalizeSettings', () => {
	it( 'reads the empty categories sent by PHP as a list', () => {
		expect( normalizeSettings( { active: '1', categories: [] } ) ).toEqual(
			{
				active: '1',
			}
		);
	} );

	it( 'returns no settings when the REST API reads null', () => {
		expect( normalizeSettings( null ) ).toEqual( {} );
	} );
} );

describe( 'the checkboxes', () => {
	it( 'are on with the 1 value and off without the key', () => {
		const checked = withCheckbox( { text: 'a' }, 'active', true );

		expect( checked ).toEqual( { text: 'a', active: '1' } );
		expect( isChecked( checked, 'active' ) ).toBe( true );
		expect( withCheckbox( checked, 'active', false ) ).toEqual( {
			text: 'a',
		} );
	} );
} );

describe( 'withCategoryText', () => {
	it( 'keeps the other texts of the category', () => {
		const settings = withCategoryText(
			{ categories: { statistics: { label: 'Analytics' } } },
			'statistics',
			'description',
			'Visits.'
		);

		expect( settings.categories ).toEqual( {
			statistics: { label: 'Analytics', description: 'Visits.' },
		} );
	} );
} );

describe( 'the presets', () => {
	it( 'are blocked until they are turned off', () => {
		const disabled = withVendorBlocked( {}, 'youtube', false );

		expect( isVendorBlocked( {}, 'youtube' ) ).toBe( true );
		expect( disabled.disabled_vendors ).toEqual( [ 'youtube' ] );
		expect( isVendorBlocked( disabled, 'youtube' ) ).toBe( false );
		expect(
			withVendorBlocked( disabled, 'youtube', true ).disabled_vendors
		).toEqual( [] );
	} );
} );

describe( 'the custom vendors', () => {
	it( 'get an id not taken by the others', () => {
		expect(
			newCustomVendor( [ { ...vendor, id: 'custom-2' } ], 'statistics' )
		).toEqual( {
			id: 'custom-3',
			name: '',
			category: 'statistics',
			patterns: [],
		} );
	} );

	it( 'are changed and removed by index', () => {
		const settings = {
			custom_vendors: [ vendor, { ...vendor, id: 'other' } ],
		};
		const changed = withCustomVendor( settings, 1, {
			...vendor,
			id: 'other',
			name: 'Other',
		} );

		expect( changed.custom_vendors?.[ 1 ]?.name ).toBe( 'Other' );
		expect( withCustomVendor( changed, 0, null ).custom_vendors ).toEqual( [
			{ ...vendor, id: 'other', name: 'Other' },
		] );
		expect( settings.custom_vendors[ 1 ]?.name ).toBe( 'Custom' );
	} );
} );
