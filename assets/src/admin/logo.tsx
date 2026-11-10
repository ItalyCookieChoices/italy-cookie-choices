/* @jsxRuntime classic */
/* @jsx createElement */
import { BaseControl, Button } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { MediaUpload } from '@wordpress/media-utils';
import { loadMedia, type Media } from './api';
import type { SettingsProps } from './fields';
import { textOf } from './settings';

/**
 * The logo of the banner, an image of the media library saved by its id.
 * @param props          The settings props.
 * @param props.settings The settings not saved yet.
 * @param props.onChange Changes the settings.
 */
export function LogoField( { settings, onChange }: SettingsProps ) {
	const id = Number( textOf( settings, 'banner_logo' ) );
	const [ preview, setPreview ] = useState< Media | null >( null );

	useEffect( () => {
		if ( ! ( id > 0 ) ) {
			setPreview( null );
			return;
		}

		loadMedia( id )
			.then( setPreview )
			.catch( () => setPreview( null ) );
	}, [ id ] );

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="icc-banner-logo"
			label={ __( 'Logo', 'italy-cookie-choices' ) }
			help={ __(
				'An image of the media library above the title of the banner.',
				'italy-cookie-choices'
			) }
		>
			<div className="icc-admin__logo">
				{ preview !== null && (
					<img src={ preview.url } alt={ preview.alt } />
				) }
				<MediaUpload
					allowedTypes={ [ 'image' ] }
					value={ id > 0 ? id : undefined }
					onSelect={ ( media: {
						id: number;
						url: string;
						alt?: string;
					} ) => {
						setPreview( { url: media.url, alt: media.alt ?? '' } );
						onChange( {
							...settings,
							banner_logo: String( media.id ),
						} );
					} }
					render={ ( { open }: { open: () => void } ) => (
						<Button variant="secondary" onClick={ open }>
							{ id > 0
								? __(
										'Replace the logo',
										'italy-cookie-choices'
									)
								: __(
										'Choose a logo',
										'italy-cookie-choices'
									) }
						</Button>
					) }
				/>
				{ id > 0 && (
					<Button
						variant="tertiary"
						isDestructive
						onClick={ () =>
							onChange( { ...settings, banner_logo: '' } )
						}
					>
						{ __( 'Remove the logo', 'italy-cookie-choices' ) }
					</Button>
				) }
			</div>
		</BaseControl>
	);
}
