/* @jsxRuntime classic */
/* @jsx createElement */
import { fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import * as api from '../api';
import { LogoField } from '../logo';

jest.mock( '../api', () => ( {
	...jest.requireActual( '../api' ),
	loadMedia: jest.fn(),
} ) );

// The media library of WordPress is not in the tests: the frame selects an image as soon as it opens.
jest.mock( '@wordpress/media-utils', () => ( {
	MediaUpload: ( {
		render: renderButton,
		onSelect,
	}: {
		render: ( props: { open: () => void } ) => JSX.Element;
		onSelect: ( media: { id: number; url: string; alt: string } ) => void;
	} ) =>
		renderButton( {
			open: () =>
				onSelect( {
					id: 42,
					url: 'https://example.com/logo.png',
					alt: 'Our logo',
				} ),
		} ),
} ) );

const mocked = jest.mocked( api );

it( 'chooses a logo from the media library', () => {
	const onChange = jest.fn();
	render( <LogoField settings={ {} } onChange={ onChange } /> );

	fireEvent.click( screen.getByRole( 'button', { name: 'Choose a logo' } ) );

	expect( onChange ).toHaveBeenCalledWith( { banner_logo: '42' } );
	expect( mocked.loadMedia ).not.toHaveBeenCalled();
} );

it( 'shows the saved logo and removes it', async () => {
	mocked.loadMedia.mockResolvedValue( {
		url: 'https://example.com/saved.png',
		alt: 'Saved logo',
	} );
	const onChange = jest.fn();
	render(
		<LogoField settings={ { banner_logo: '7' } } onChange={ onChange } />
	);

	const image = await screen.findByRole( 'img', { name: 'Saved logo' } );
	expect( image.getAttribute( 'src' ) ).toBe(
		'https://example.com/saved.png'
	);
	expect( mocked.loadMedia ).toHaveBeenCalledWith( 7 );
	expect(
		screen.getByRole( 'button', { name: 'Replace the logo' } )
	).toBeTruthy();

	fireEvent.click(
		screen.getByRole( 'button', { name: 'Remove the logo' } )
	);

	expect( onChange ).toHaveBeenCalledWith( { banner_logo: '' } );
} );
