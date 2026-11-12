/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import type { Languages } from '../settings';
import { LanguagesTab } from '../tabs/languages';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	loadSiteTexts: jest.fn(),
	saveSiteTexts: jest.fn(),
	draftSiteTexts: jest.fn(),
} ) );

const mocked = jest.mocked( api );

const LANGUAGES: Languages = {
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
		{
			id: 3,
			name: 'Español',
			url: 'https://example.com/es/',
			language: 'es-ES',
			canTranslate: false,
		},
	],
};

function renderTab( settings = {} ) {
	const callbacks = { onChange: jest.fn(), onMessage: jest.fn() };
	render(
		<LanguagesTab
			languages={ LANGUAGES }
			categories={ [] }
			settings={ settings }
			{ ...callbacks }
		/>
	);

	return callbacks;
}

beforeEach( () => {
	jest.resetAllMocks();
	mocked.loadSiteTexts.mockResolvedValue( {
		texts: { text: '' },
		policySuggestion: '',
	} );
} );

it( 'fills the empty texts of this site from a connected site', async () => {
	mocked.draftSiteTexts.mockResolvedValue( {
		texts: { text: 'Wir verwenden Cookies', button_text: 'Akzeptieren' },
		policySuggestion: '',
	} );
	const { onChange } = renderTab( { text: '', button_text: 'Accetta' } );

	fireEvent.click(
		screen.getByRole( 'button', { name: 'Copy the empty texts' } )
	);

	await waitFor( () =>
		expect( onChange ).toHaveBeenCalledWith( {
			text: 'Wir verwenden Cookies',
			button_text: 'Accetta',
		} )
	);
	expect( mocked.draftSiteTexts ).toHaveBeenCalledWith( 1, 2, false );
} );

it( 'translates only from the sites with a translation service', async () => {
	mocked.draftSiteTexts.mockResolvedValue( {
		texts: { text: '[it-IT] Wir verwenden Cookies' },
		policySuggestion: '',
	} );
	renderTab();
	const translate = screen.getByRole( 'button', {
		name: 'Translate the empty texts',
	} ) as HTMLButtonElement;

	fireEvent.change( screen.getByLabelText( 'Fill from' ), {
		target: { value: '3' },
	} );
	expect( translate.disabled ).toBe( true );

	fireEvent.change( screen.getByLabelText( 'Fill from' ), {
		target: { value: '2' },
	} );
	fireEvent.click( translate );

	await waitFor( () =>
		expect( mocked.draftSiteTexts ).toHaveBeenCalledWith( 1, 2, true )
	);
} );

it( 'proposes the translation of the policy page when the link is empty', async () => {
	mocked.loadSiteTexts.mockImplementation( ( siteId: number ) =>
		Promise.resolve( {
			texts: { url: 'https://example.com/de/datenschutz/' },
			policySuggestion:
				siteId === 1 ? 'https://example.com/it/cookie-policy/' : '',
		} )
	);
	const { onChange } = renderTab( { url: '' } );

	fireEvent.click(
		await screen.findByRole( 'button', { name: 'Use this link' } )
	);

	expect( onChange ).toHaveBeenCalledWith( {
		url: 'https://example.com/it/cookie-policy/',
	} );
} );

it( 'edits and saves the texts of a connected site', async () => {
	mocked.loadSiteTexts.mockImplementation( ( siteId: number ) =>
		Promise.resolve( {
			texts: siteId === 2 ? { text: 'Alt' } : {},
			policySuggestion: '',
		} )
	);
	mocked.saveSiteTexts.mockResolvedValue( {
		texts: { text: 'Neu' },
		policySuggestion: '',
	} );
	const { onMessage } = renderTab();

	const text = await screen.findByDisplayValue( 'Alt' );
	fireEvent.change( text, { target: { value: 'Neu' } } );
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Save the texts of Deutsch' } )
	);

	await waitFor( () =>
		expect( mocked.saveSiteTexts ).toHaveBeenCalledWith( 2, {
			text: 'Neu',
		} )
	);
	await waitFor( () =>
		expect( onMessage ).toHaveBeenCalledWith( {
			status: 'success',
			text: 'The texts of Deutsch were saved.',
		} )
	);
} );

it( 'fills the empty texts of a connected site from this site', async () => {
	mocked.loadSiteTexts.mockResolvedValue( {
		texts: { text: '' },
		policySuggestion: '',
	} );
	mocked.draftSiteTexts.mockResolvedValue( {
		texts: { text: '[de-DE] Usiamo i cookie' },
		policySuggestion: '',
	} );
	renderTab();

	fireEvent.click(
		await screen.findByRole( 'button', {
			name: 'Translate the empty texts from this site',
		} )
	);

	expect(
		await screen.findByDisplayValue( '[de-DE] Usiamo i cookie' )
	).toBeTruthy();
	expect( mocked.draftSiteTexts ).toHaveBeenCalledWith( 2, 1, true );
	expect( mocked.saveSiteTexts ).not.toHaveBeenCalled();
} );
