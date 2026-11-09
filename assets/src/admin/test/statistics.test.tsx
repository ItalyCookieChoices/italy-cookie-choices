/* @jsxRuntime classic */
/* @jsx createElement */
import { render, screen, within } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { ConsentStatistics } from '../statistics';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	loadStatistics: jest.fn(),
} ) );

const mocked = jest.mocked( api );

const STATISTICS = {
	categories: [
		{ id: 'statistics', label: 'Statistics' },
		{ id: 'marketing', label: 'Marketing' },
	],
	months: [
		{
			month: '2026-11',
			choices: 10,
			acceptAll: 5,
			rejectAll: 3,
			partial: 1,
			withdrawals: 1,
			categories: { statistics: 6, marketing: 5 },
		},
	],
};

it( 'shows a row for each month, with the share of the choices', async () => {
	mocked.loadStatistics.mockResolvedValue( STATISTICS );

	render( <ConsentStatistics /> );

	const table = await screen.findByRole( 'table' );
	expect(
		within( table )
			.getAllByRole( 'columnheader' )
			.map( ( cell ) => cell.textContent )
	).toEqual( [
		'Month',
		'Choices',
		'Accept all',
		'Reject all',
		'Partial',
		'Withdrawals',
		'Statistics',
		'Marketing',
	] );
	expect(
		within( table )
			.getAllByRole( 'cell' )
			.map( ( cell ) => cell.textContent )
	).toEqual( [
		'10',
		'5 (50%)',
		'3 (30%)',
		'1 (10%)',
		'1 (10%)',
		'6 (60%)',
		'5 (50%)',
	] );
	expect( within( table ).getByRole( 'rowheader' ).textContent ).toBe(
		'2026-11'
	);
} );

it( 'says when no choice is logged yet', async () => {
	mocked.loadStatistics.mockResolvedValue( {
		categories: STATISTICS.categories,
		months: [],
	} );

	render( <ConsentStatistics /> );

	expect( await screen.findByText( 'No choice logged yet.' ) ).toBeTruthy();
	expect( screen.queryByRole( 'table' ) ).toBeNull();
} );

it( 'shows the error of the request', async () => {
	mocked.loadStatistics.mockRejectedValue( { message: 'Forbidden' } );

	render( <ConsentStatistics /> );

	// The notice is also spoken to the screen readers.
	expect(
		await screen.findByText( 'Forbidden', {
			selector: '.components-notice__content',
		} )
	).toBeTruthy();
} );
