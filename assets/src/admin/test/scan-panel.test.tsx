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
	saveScanSchedule: jest.fn(),
	suggestForScan: jest.fn(),
} ) );

const mocked = jest.mocked( api );

const VENDORS = [
	{ id: 'youtube', name: 'YouTube', category: 'marketing' },
	{ id: 'hotjar', name: 'Hotjar', category: 'statistics' },
];

const CATEGORIES = [
	{ id: 'necessary', required: true, label: 'Necessary', description: '' },
	{
		id: 'preferences',
		required: false,
		label: 'Preferences',
		description: '',
	},
];

function renderPanel( settings = {}, hasAssistant = false ) {
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
			siteCookieCategory="necessary"
			categories={ CATEGORIES }
			hasAssistant={ hasAssistant }
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

it( 'starts from the pages of the schedule and shows its last report', () => {
	render(
		<ScanPanel
			settings={ {} }
			onChange={ jest.fn() }
			vendors={ VENDORS }
			postTypes={ [ { slug: 'page', name: 'Pages' } ] }
			siteCookieCategory="necessary"
			categories={ [] }
			hasAssistant={ false }
			customCategory="marketing"
			schedule={ {
				frequency: 'weekly',
				home: true,
				postTypes: [ 'page' ],
				categories: [],
				posts: [],
				email: true,
				adminEmail: 'owner@example.com',
				report: {
					date: 'November 1, 2026',
					pages: 3,
					errors: 0,
					vendors: [ 'Hotjar' ],
					hosts: [],
				},
			} }
		/>
	);

	expect(
		( screen.getByLabelText( 'Home page' ) as HTMLInputElement ).checked
	).toBe( true );
	expect(
		( screen.getByLabelText( 'Pages' ) as HTMLInputElement ).checked
	).toBe( true );
	expect(
		(
			screen.getByLabelText(
				'Scan the chosen pages'
			) as HTMLSelectElement
		 ).value
	).toBe( 'weekly' );
	expect(
		screen.getByText(
			'Last scheduled scan: November 1, 2026, 3 pages. Vendors not blocked: Hotjar. Unknown third parties: -.'
		)
	).toBeTruthy();
} );

it( 'saves the schedule with the chosen pages', async () => {
	mocked.saveScanSchedule.mockResolvedValue( {
		frequency: 'daily',
		home: true,
		postTypes: [],
		categories: [],
		posts: [],
		email: true,
		adminEmail: 'owner@example.com',
		report: null,
	} );
	renderPanel();

	fireEvent.click( screen.getByLabelText( 'Home page' ) );
	fireEvent.change( screen.getByLabelText( 'Scan the chosen pages' ), {
		target: { value: 'daily' },
	} );
	fireEvent.click( screen.getByLabelText( /Email the new findings to/ ) );
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Save the schedule' } )
	);

	await waitFor( () =>
		expect( mocked.saveScanSchedule ).toHaveBeenCalledWith( {
			home: true,
			postTypes: [],
			categories: [],
			posts: [],
			frequency: 'daily',
			email: true,
		} )
	);
	expect(
		await screen.findByText( 'The schedule is saved.', {
			selector: '.components-notice__content',
		} )
	).toBeTruthy();
} );

it( 'adds an unknown third party with the suggestion of the AI provider, when asked', async () => {
	mocked.scanTargets.mockResolvedValue( {
		targets: [ { id: 0, title: 'Home page', url: 'https://example.com/' } ],
		truncated: false,
	} );
	mocked.scanPages.mockResolvedValue( [
		{
			id: 0,
			url: 'https://example.com/',
			vendors: [],
			hosts: [ 'widget.chat.example' ],
			error: '',
		},
	] );
	mocked.suggestForScan.mockResolvedValue( {
		hosts: [
			{
				host: 'widget.chat.example',
				name: 'Chat Widget',
				category: 'preferences',
				purpose: 'Shows the chat.',
				vendor: '',
			},
		],
		cookies: [],
	} );
	const onChange = renderPanel( {}, true );

	fireEvent.click( screen.getByLabelText( 'Home page' ) );
	fireEvent.click( screen.getByRole( 'button', { name: 'Scan the site' } ) );
	fireEvent.click(
		await screen.findByRole( 'button', { name: 'Suggest with AI' } )
	);

	expect(
		await screen.findByText(
			'Suggested: Chat Widget, Preferences. Shows the chat.'
		)
	).not.toBeNull();
	expect( mocked.suggestForScan ).toHaveBeenCalledWith(
		[ 'widget.chat.example' ],
		[]
	);
	expect( onChange ).not.toHaveBeenCalled();

	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Add widget.chat.example as a custom vendor',
		} )
	);

	expect( onChange ).toHaveBeenLastCalledWith( {
		custom_vendors: [
			{
				id: 'custom-1',
				name: 'Chat Widget',
				category: 'preferences',
				patterns: [ 'widget.chat.example' ],
			},
		],
	} );
} );

it( 'discards a suggestion of the AI provider', async () => {
	mocked.scanTargets.mockResolvedValue( {
		targets: [ { id: 0, title: 'Home page', url: 'https://example.com/' } ],
		truncated: false,
	} );
	mocked.scanPages.mockResolvedValue( [
		{
			id: 0,
			url: 'https://example.com/',
			vendors: [],
			hosts: [ 'cdn.example.org' ],
			error: '',
		},
	] );
	mocked.suggestForScan.mockResolvedValue( {
		hosts: [
			{
				host: 'cdn.example.org',
				name: 'CDN',
				category: 'preferences',
				purpose: 'Serves files.',
				vendor: '',
			},
		],
		cookies: [],
	} );
	const onChange = renderPanel( {}, true );

	fireEvent.click( screen.getByLabelText( 'Home page' ) );
	fireEvent.click( screen.getByRole( 'button', { name: 'Scan the site' } ) );
	fireEvent.click(
		await screen.findByRole( 'button', { name: 'Suggest with AI' } )
	);
	fireEvent.click(
		await screen.findByRole( 'button', { name: 'Discard the suggestion' } )
	);
	fireEvent.click(
		screen.getByRole( 'button', {
			name: 'Add cdn.example.org as a custom vendor',
		} )
	);

	expect( screen.queryByText( /Suggested:/ ) ).toBeNull();
	expect( onChange ).toHaveBeenLastCalledWith( {
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

it( 'hides the AI suggestions without an AI provider', async () => {
	mocked.scanTargets.mockResolvedValue( {
		targets: [ { id: 0, title: 'Home page', url: 'https://example.com/' } ],
		truncated: false,
	} );
	mocked.scanPages.mockResolvedValue( [
		{
			id: 0,
			url: 'https://example.com/',
			vendors: [],
			hosts: [ 'cdn.example.org' ],
			error: '',
		},
	] );
	renderPanel();

	fireEvent.click( screen.getByLabelText( 'Home page' ) );
	fireEvent.click( screen.getByRole( 'button', { name: 'Scan the site' } ) );

	expect( await screen.findByText( 'cdn.example.org' ) ).not.toBeNull();
	expect(
		screen.queryByRole( 'button', { name: 'Suggest with AI' } )
	).toBeNull();
} );
