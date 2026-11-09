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

it( 'explains why reCAPTCHA starts turned off', () => {
	render(
		<BlockingTab
			settings={ { disabled_vendors: [ 'google-recaptcha' ] } }
			onChange={ jest.fn() }
			categories={ data.categories }
			vendors={ [
				{
					id: 'google-recaptcha',
					name: 'Google reCAPTCHA',
					category: 'marketing',
				},
			] }
		/>
	);

	const recaptcha = screen.getByRole( 'checkbox', {
		name: 'Google reCAPTCHA',
	} );
	expect( ( recaptcha as HTMLInputElement ).checked ).toBe( false );
	expect(
		screen.getByText(
			'Waits for the Marketing consent. The forms that use it wait for the consent too, turn it on only when they can.'
		)
	).toBeTruthy();
} );

it( 'adds a cookie to a custom vendor', () => {
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

	fireEvent.click(
		screen.getByRole( 'button', { name: 'Add a cookie of Ads' } )
	);

	expect( onChange ).toHaveBeenLastCalledWith( {
		custom_vendors: [
			{ ...vendor, cookies: [ { name: '', duration: '', purpose: '' } ] },
		],
	} );
} );

it( 'edits and removes a cookie of a custom vendor', () => {
	const onChange = jest.fn();
	const cookie = { name: '_ads', duration: '1 year', purpose: 'Ads' };
	const vendor = {
		id: 'ads',
		name: 'Ads',
		category: 'marketing',
		patterns: [ 'ads.example.com' ],
		cookies: [ cookie ],
	};
	render(
		<BlockingTab
			settings={ { custom_vendors: [ vendor ] } }
			onChange={ onChange }
			categories={ data.categories }
			vendors={ data.vendors }
		/>
	);

	fireEvent.change( screen.getByRole( 'textbox', { name: 'Duration' } ), {
		target: { value: '2 years' },
	} );
	expect( onChange ).toHaveBeenLastCalledWith( {
		custom_vendors: [
			{ ...vendor, cookies: [ { ...cookie, duration: '2 years' } ] },
		],
	} );

	fireEvent.click(
		screen.getByRole( 'button', { name: 'Remove the cookie _ads' } )
	);
	expect( onChange ).toHaveBeenLastCalledWith( {
		custom_vendors: [ { ...vendor, cookies: [] } ],
	} );
} );

it( 'adds a site cookie in the necessary category', () => {
	const onChange = jest.fn();
	render(
		<BlockingTab
			settings={ {} }
			onChange={ onChange }
			categories={ data.categories }
			vendors={ data.vendors }
		/>
	);

	fireEvent.click(
		screen.getByRole( 'button', { name: 'Add a site cookie' } )
	);

	expect( onChange ).toHaveBeenLastCalledWith( {
		site_cookies: [
			{ name: '', category: 'necessary', duration: '', purpose: '' },
		],
	} );
} );

it( 'moves a site cookie to another category', () => {
	const onChange = jest.fn();
	const cookie = {
		name: 'wp_lang',
		category: 'necessary',
		duration: 'Session',
		purpose: 'Language',
	};
	render(
		<BlockingTab
			settings={ { site_cookies: [ cookie ] } }
			onChange={ onChange }
			categories={ data.categories }
			vendors={ data.vendors }
		/>
	);

	fireEvent.change(
		screen.getByRole( 'combobox', { name: 'Cookie category' } ),
		{ target: { value: 'statistics' } }
	);

	expect( onChange ).toHaveBeenLastCalledWith( {
		site_cookies: [ { ...cookie, category: 'statistics' } ],
	} );
} );
