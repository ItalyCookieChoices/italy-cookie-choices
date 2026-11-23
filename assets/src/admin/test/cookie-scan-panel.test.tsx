/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { CookieScanPanel } from '../cookie-scan-panel';
import * as frameScan from '../frame-scan';
import type { Settings } from '../settings';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	frameScanUrl: jest.fn(),
	suggestForScan: jest.fn(),
} ) );

jest.mock( '../frame-scan', () => ( {
	...jest.requireActual( '../frame-scan' ),
	scanInFrame: jest.fn(),
	loadsAsAnonymousVisitor: jest.fn(),
} ) );

const mockedApi = jest.mocked( api );
const mockedFrames = jest.mocked( frameScan );

const VENDORS = [
	{
		id: 'google-analytics',
		name: 'Google Analytics',
		category: 'statistics',
		cookies: [ '_ga' ],
	},
];
const HOME = { id: 0, title: 'Home', url: 'https://example.com/' };
const SHOP = { id: 2, title: 'Shop', url: 'https://example.com/shop/' };

const CATEGORIES = [
	{ id: 'necessary', required: true, label: 'Necessary', description: '' },
	{
		id: 'preferences',
		required: false,
		label: 'Preferences',
		description: '',
	},
];

function renderPanel( settings: Settings = {}, hasAssistant = false ) {
	const onChange = jest.fn();
	render(
		<CookieScanPanel
			settings={ settings }
			onChange={ onChange }
			vendors={ VENDORS }
			targets={ () => Promise.resolve( [ HOME, SHOP ] ) }
			siteCookieCategory="necessary"
			categories={ CATEGORIES }
			hasAssistant={ hasAssistant }
			disabled={ false }
		/>
	);

	return onChange;
}

beforeEach( () => {
	mockedFrames.loadsAsAnonymousVisitor.mockReturnValue( true );
	mockedApi.frameScanUrl.mockImplementation( ( url ) =>
		Promise.resolve( `${ url }?icc_scan=token` )
	);
} );

it( 'loads the pages in scan mode and lists their cookies with their owners', async () => {
	mockedFrames.scanInFrame
		.mockResolvedValueOnce( [
			{ name: '_ga', path: '/', duration: 63072000, httpOnly: false },
		] )
		.mockResolvedValueOnce( [
			{
				name: 'woocommerce_cart_hash',
				path: '/',
				duration: null,
				httpOnly: false,
			},
		] );
	renderPanel();

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Scan the cookies in the browser',
		} )
	);

	expect( await screen.findByText( 'Scanned 2 of 2 pages.' ) ).not.toBeNull();
	expect( mockedFrames.scanInFrame ).toHaveBeenCalledWith(
		'https://example.com/?icc_scan=token',
		expect.any( HTMLElement )
	);
	expect( screen.getByText( '_ga' ) ).not.toBeNull();
	expect( screen.getByText( '2 years' ) ).not.toBeNull();
	expect( screen.getByText( 'A cookie of Google Analytics' ) ).not.toBeNull();
	expect( screen.getByText( 'woocommerce_cart_hash' ) ).not.toBeNull();
	expect( screen.getByText( 'Unknown' ) ).not.toBeNull();
} );

it( 'adds an unknown cookie as a site cookie with its duration', async () => {
	mockedFrames.scanInFrame.mockResolvedValue( [
		{
			name: 'pll_language',
			path: '/',
			duration: 31536000,
			httpOnly: false,
		},
	] );
	const onChange = renderPanel();

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Scan the cookies in the browser',
		} )
	);
	fireEvent.click(
		await screen.findByRole( 'button', {
			name: 'Add pll_language as a site cookie',
		} )
	);

	expect( onChange ).toHaveBeenCalledWith( {
		site_cookies: [
			{
				name: 'pll_language',
				category: 'necessary',
				duration: '1 year',
				purpose: '',
			},
		],
	} );
} );

it( 'shows a site cookie as known', async () => {
	mockedFrames.scanInFrame.mockResolvedValue( [
		{ name: 'pll_language', path: '/', duration: null, httpOnly: false },
	] );
	renderPanel( {
		site_cookies: [
			{
				name: 'pll_language',
				category: 'preferences',
				duration: '',
				purpose: '',
			},
		],
	} );

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Scan the cookies in the browser',
		} )
	);

	expect( await screen.findAllByText( 'A site cookie' ) ).toHaveLength( 1 );
	expect(
		screen.queryByRole( 'button', { name: /as a site cookie/ } )
	).toBeNull();
} );

it( 'reports the pages that refuse the frame', async () => {
	mockedFrames.scanInFrame
		.mockRejectedValueOnce( new frameScan.FrameScanError( 'refused' ) )
		.mockResolvedValueOnce( [] );
	renderPanel();

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Scan the cookies in the browser',
		} )
	);

	// The notice, and its copy announced to the screen readers.
	const notices = await screen.findAllByText(
		/https:\/\/example.com\/: The page refused to open in a frame/
	);
	expect(
		notices.some(
			( notice ) =>
				notice.closest( '.components-notice__content' ) !== null
		)
	).toBe( true );
	await waitFor( () =>
		expect( screen.getByText( 'Scanned 2 of 2 pages.' ) ).not.toBeNull()
	);
	expect( screen.getByText( 'The pages set no cookie.' ) ).not.toBeNull();
} );

it( 'warns when the browser loads the pages as the administrator', () => {
	mockedFrames.loadsAsAnonymousVisitor.mockReturnValue( false );

	renderPanel();

	expect(
		screen.getAllByText( /Use Chrome or Edge/ ).length
	).toBeGreaterThan( 0 );
} );

it( 'adds an unknown cookie with the suggestion of the AI provider, when asked', async () => {
	mockedFrames.scanInFrame.mockResolvedValue( [
		{
			name: 'pll_language',
			path: '/',
			duration: 31536000,
			httpOnly: false,
		},
		{ name: '_ga', path: '/', duration: 63072000, httpOnly: false },
	] );
	mockedApi.suggestForScan.mockResolvedValue( {
		hosts: [],
		cookies: [
			{
				name: 'pll_language',
				provider: 'Polylang',
				category: 'preferences',
				purpose: 'Keeps the language.',
			},
		],
	} );
	const onChange = renderPanel( {}, true );

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Scan the cookies in the browser',
		} )
	);
	fireEvent.click(
		await screen.findByRole( 'button', { name: 'Suggest with AI' } )
	);

	expect(
		await screen.findByText(
			'Suggested: Polylang, Preferences. Keeps the language.'
		)
	).not.toBeNull();
	// Only the unknown cookies are sent.
	expect( mockedApi.suggestForScan ).toHaveBeenCalledWith(
		[],
		[ 'pll_language' ]
	);

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Add pll_language as a site cookie',
		} )
	);

	expect( onChange ).toHaveBeenCalledWith( {
		site_cookies: [
			{
				name: 'pll_language',
				category: 'preferences',
				duration: '1 year',
				purpose: 'Keeps the language.',
			},
		],
	} );
} );

it( 'reports when the AI provider does not answer', async () => {
	mockedFrames.scanInFrame.mockResolvedValue( [
		{ name: 'pll_language', path: '/', duration: null, httpOnly: false },
	] );
	mockedApi.suggestForScan.mockRejectedValue(
		new Error( 'The provider is down.' )
	);
	renderPanel( {}, true );

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Scan the cookies in the browser',
		} )
	);
	fireEvent.click(
		await screen.findByRole( 'button', { name: 'Suggest with AI' } )
	);

	expect(
		( await screen.findAllByText( 'The provider is down.' ) ).length
	).toBeGreaterThan( 0 );
} );
