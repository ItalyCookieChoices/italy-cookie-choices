/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { BlockingTab } from '../tabs/blocking';
import { data } from './fixtures/data';

it( 'turns off a preset', () => {
	const onChange = jest.fn();
	render(
		<BlockingTab
			settings={ {} }
			onChange={ onChange }
			categories={ data.categories }
			vendors={ data.vendors }
		/>
	);

	const youtube = screen.getByRole( 'checkbox', { name: 'YouTube' } );
	expect( ( youtube as HTMLInputElement ).checked ).toBe( true );
	expect(
		screen.getByText( 'Waits for the Marketing consent.' )
	).toBeTruthy();
	fireEvent.click( youtube );

	expect( onChange ).toHaveBeenCalledWith( {
		disabled_vendors: [ 'youtube' ],
	} );
} );

it( 'adds a custom vendor in the last category', () => {
	const onChange = jest.fn();
	render(
		<BlockingTab
			settings={ {} }
			onChange={ onChange }
			categories={ data.categories }
			vendors={ data.vendors }
		/>
	);

	fireEvent.click( screen.getByRole( 'button', { name: 'Add a vendor' } ) );

	expect( onChange ).toHaveBeenCalledWith( {
		custom_vendors: [
			{ id: 'custom-1', name: '', category: 'marketing', patterns: [] },
		],
	} );
} );

it( 'edits and removes a custom vendor', () => {
	const onChange = jest.fn();
	const vendor = {
		id: 'ads',
		name: 'Ads',
		category: 'marketing',
		patterns: [ 'ads.example.com' ],
	};
	render(
		<BlockingTab
			settings={ { custom_vendors: [ vendor ] } }
			onChange={ onChange }
			categories={ data.categories }
			vendors={ data.vendors }
		/>
	);

	const category = screen.getByRole( 'combobox', { name: 'Category' } );
	expect(
		Array.from( ( category as HTMLSelectElement ).options ).map(
			( option ) => option.value
		)
	).toEqual( [ 'statistics', 'marketing' ] );
	fireEvent.change( category, { target: { value: 'statistics' } } );
	expect( onChange ).toHaveBeenLastCalledWith( {
		custom_vendors: [ { ...vendor, category: 'statistics' } ],
	} );

	fireEvent.click( screen.getByRole( 'button', { name: 'Remove Ads' } ) );
	expect( onChange ).toHaveBeenLastCalledWith( { custom_vendors: [] } );
} );
