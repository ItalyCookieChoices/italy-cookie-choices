/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { PolicyTranslations } from '../policy-translations';
import { parseAdminData } from '../settings';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	savePolicyTranslation: jest.fn(),
} ) );

const mocked = jest.mocked( api );

const ROWS = [
	{
		language: 'de',
		name: 'Deutsch',
		current: '',
		proposal: 'https://example.com/de/cookie-richtlinie/',
	},
	{
		language: 'es',
		name: 'Español',
		current: 'https://example.com/es/politica/',
		proposal: '',
	},
];

beforeEach( () => jest.resetAllMocks() );

it( 'shows the policy link of each language and its proposal', () => {
	render( <PolicyTranslations rows={ ROWS } /> );

	expect(
		screen.getByText(
			'Not translated: the banner links the page of the main language.'
		)
	).toBeTruthy();
	expect(
		screen.getByText( 'https://example.com/de/cookie-richtlinie/' )
	).toBeTruthy();
	expect(
		screen.getByText( 'https://example.com/es/politica/' )
	).toBeTruthy();
	expect(
		screen.queryByRole( 'button', { name: 'Use this link for Español' } )
	).toBeNull();
} );

it( 'uses the proposal of a language', async () => {
	mocked.savePolicyTranslation.mockResolvedValue( [
		{ ...ROWS[ 0 ]!, current: ROWS[ 0 ]!.proposal },
		ROWS[ 1 ]!,
	] );
	render( <PolicyTranslations rows={ ROWS } /> );

	fireEvent.click(
		screen.getByRole( 'button', { name: 'Use this link for Deutsch' } )
	);

	await waitFor( () =>
		expect( mocked.savePolicyTranslation ).toHaveBeenCalledWith( 'de' )
	);
	expect(
		await screen.findByText( 'The policy link of Deutsch is saved.', {
			selector: '.components-notice__content',
		} )
	).toBeTruthy();
	expect(
		screen.queryByRole( 'button', { name: 'Use this link for Deutsch' } )
	).toBeNull();
} );

it( 'shows nothing without a multilingual plugin', () => {
	const { container } = render( <PolicyTranslations rows={ [] } /> );

	expect( container.innerHTML ).toBe( '' );
} );

it( 'reads the rows printed by the page', () => {
	expect(
		parseAdminData( { policyTranslations: [ ROWS[ 0 ], { language: 1 } ] } )
			.policyTranslations
	).toEqual( [ ROWS[ 0 ] ] );
} );
