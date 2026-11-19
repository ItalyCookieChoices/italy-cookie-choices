/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { CustomScriptList } from '../custom-scripts';
import { data } from './fixtures/data';

const chat = {
	name: 'Chat',
	category: 'statistics',
	position: 'head' as const,
	code: '<script>chat();</script>',
};

it( 'adds a script in the last category at the end of the body', () => {
	const onChange = jest.fn();
	render(
		<CustomScriptList
			scripts={ [] }
			categories={ data.categories }
			canEdit
			onChange={ onChange }
		/>
	);

	fireEvent.click( screen.getByRole( 'button', { name: 'Add a script' } ) );

	expect( onChange ).toHaveBeenCalledWith( [
		{ name: '', category: 'marketing', position: 'body', code: '' },
	] );
} );

it( 'edits the code and the position of a script', () => {
	const onChange = jest.fn();
	render(
		<CustomScriptList
			scripts={ [ chat ] }
			categories={ data.categories }
			canEdit
			onChange={ onChange }
		/>
	);

	fireEvent.change( screen.getByRole( 'textbox', { name: 'Code' } ), {
		target: { value: '<script>other();</script>' },
	} );
	expect( onChange ).toHaveBeenLastCalledWith( [
		{ ...chat, code: '<script>other();</script>' },
	] );

	fireEvent.change( screen.getByRole( 'combobox', { name: 'Position' } ), {
		target: { value: 'body' },
	} );
	expect( onChange ).toHaveBeenLastCalledWith( [
		{ ...chat, position: 'body' },
	] );
} );

it( 'removes a script', () => {
	const onChange = jest.fn();
	render(
		<CustomScriptList
			scripts={ [ chat ] }
			categories={ data.categories }
			canEdit
			onChange={ onChange }
		/>
	);

	fireEvent.click(
		screen.getByRole( 'button', { name: 'Remove the script Chat' } )
	);

	expect( onChange ).toHaveBeenCalledWith( [] );
} );

it( 'only lists the scripts to the users who cannot save unfiltered HTML', () => {
	render(
		<CustomScriptList
			scripts={ [ chat ] }
			categories={ data.categories }
			canEdit={ false }
			onChange={ jest.fn() }
		/>
	);

	expect( screen.getByText( /Only the administrators/ ) ).toBeTruthy();
	expect( screen.getByText( 'Chat' ) ).toBeTruthy();
	expect( screen.queryByRole( 'textbox' ) ).toBeNull();
	expect( screen.queryByRole( 'button' ) ).toBeNull();
} );
