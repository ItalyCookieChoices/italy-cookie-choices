/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { ScanPanel } from '../scan-panel';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	searchCategories: jest.fn(),
	searchPosts: jest.fn(),
	scanTargets: jest.fn(),
	scanPages: jest.fn(),
} ) );

const mocked = jest.mocked( api );

const VENDORS = [
	{ id: 'youtube', name: 'YouTube', category: 'marketing' },
	{ id: 'hotjar', name: 'Hotjar', category: 'statistics' },
];

function renderPanel( settings = {} ) {
	const onChange = jest.fn();
	render(
		<ScanPanel
			settings={ settings }
			onChange={ onChange }
			vendors={ VENDORS }
			postTypes={ [
				{ slug: 'page', name: 'Pages' },
				{ slug: 'post', name: 'Posts' },
			] }
			customCategory="marketing"
		/>
	);

	return onChange;
}

beforeEach( () => {
	jest.resetAllMocks();
	mocked.searchCategories.mockResolvedValue( [ { id: 3, name: 'News' } ] );
	mocked.searchPosts.mockResolvedValue( [] );
} );

it( 'scans the chosen pages in batches and lists what it found', async () => {
	mocked.scanTargets.mockResolvedValue( {
		targets: [
			{ id: 0, title: 'Home page', url: 'https://example.com/' },
			{ id: 2, title: 'About', url: 'https://example.com/about/' },
		],
		truncated: false,
	} );
	mocked.scanPages.mockResolvedValue( [
		{
			id: 0,
			url: 'https://example.com/',
			vendors: [ 'youtube', 'hotjar' ],
			hosts: [ 'cdn.example.org' ],
			error: '',
		},
		{
			id: 2,
			url: 'https://example.com/about/',
			vendors: [],
			hosts: [],
			error: 'The page answered with the status 500.',
		},
	] );
	renderPanel( { disabled_vendors: [ 'hotjar' ] } );

	fireEvent.click( screen.getByLabelText( 'Home page' ) );
	fireEvent.click( screen.getByLabelText( 'Pages' ) );
	fireEvent.click( screen.getByRole( 'button', { name: 'Scan the site' } ) );

	expect( await screen.findByText( 'Scanned 2 of 2 pages.' ) ).toBeTruthy();
	expect( mocked.scanTargets ).toHaveBeenCalledWith( {
		home: true,
		postTypes: [ 'page' ],
		categories: [],
		posts: [],
	} );
	expect( mocked.scanPages ).toHaveBeenCalledWith( [ 0, 2 ] );
	expect( screen.getByLabelText( 'Block YouTube' ) ).toBeTruthy();
	expect( screen.getByText( 'cdn.example.org' ) ).toBeTruthy();
	expect(
		screen.getByText(
			'https://example.com/about/: The page answered with the status 500.',
			{ selector: '.components-notice__content' }
		)
	).toBeTruthy();
} );

it( 'blocks a vendor found and adds an unknown third party as a custom vendor', async () => {
	mocked.scanTargets.mockResolvedValue( {
		targets: [ { id: 0, title: 'Home page', url: 'https://example.com/' } ],
		truncated: false,
	} );
	mocked.scanPages.mockResolvedValue( [
		{
			id: 0,
			url: 'https://example.com/',
			vendors: [ 'hotjar' ],
			hosts: [ 'cdn.example.org' ],
			error: '',
		},
	] );
	const onChange = renderPanel( { disabled_vendors: [ 'hotjar' ] } );

	fireEvent.click( screen.getByLabelText( 'Home page' ) );
	fireEvent.click( screen.getByRole( 'button', { name: 'Scan the site' } ) );
	fireEvent.click( await screen.findByLabelText( 'Block Hotjar' ) );

	expect( onChange ).toHaveBeenCalledWith( { disabled_vendors: [] } );

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Add cdn.example.org as a custom vendor',
		} )
	);

	expect( onChange ).toHaveBeenLastCalledWith( {
		disabled_vendors: [ 'hotjar' ],
		custom_vendors: [
			{
				id: 'custom-1',
				name: 'cdn.example.org',
				category: 'marketing',
				patterns: [ 'cdn.example.org' ],
			},
		],
	} );
} );

it( 'needs something to scan', () => {
	renderPanel();

	expect(
		(
			screen.getByRole( 'button', {
				name: 'Scan the site',
			} ) as HTMLButtonElement
		 ).disabled
	).toBe( true );
} );

it( 'tells why the scan failed', async () => {
	mocked.scanTargets.mockRejectedValue( { message: 'Forbidden.' } );
	renderPanel();

	fireEvent.click( screen.getByLabelText( 'Home page' ) );
	fireEvent.click( screen.getByRole( 'button', { name: 'Scan the site' } ) );

	await waitFor( () =>
		expect(
			screen.getByText( 'Forbidden.', {
				selector: '.components-notice__content',
			} )
		).toBeTruthy()
	);
} );
