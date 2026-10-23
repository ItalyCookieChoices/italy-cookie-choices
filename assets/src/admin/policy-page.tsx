/* @jsxRuntime classic */
/* @jsx createElement */
import { ComboboxControl } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { searchPages, type Page } from './api';
import type { SettingsProps } from './fields';
import { textOf } from './settings';

const SEARCH_DELAY = 300;

/**
 * The options of the found pages, with the saved page when it is not among them.
 * @param pages The found pages.
 * @param slug  The saved slug.
 */
export function pageOptions(
	pages: readonly Page[],
	slug: string
): Array< { value: string; label: string } > {
	const options = pages.map( ( page ) => ( {
		value: page.slug,
		label: page.title,
	} ) );
	if ( slug === '' || pages.some( ( page ) => page.slug === slug ) ) {
		return options;
	}

	return [ { value: slug, label: slug }, ...options ];
}

/**
 * The policy page, searched only when the site owner types (#154).
 * Choosing it fills the link of the banner, the banner does not open by itself on that page.
 * @param props          The settings props.
 * @param props.settings The settings.
 * @param props.onChange Changes the settings.
 */
export function PolicyPage( { settings, onChange }: SettingsProps ) {
	const [ search, setSearch ] = useState( '' );
	const [ pages, setPages ] = useState< Page[] >( [] );

	useEffect( () => {
		if ( search.trim() === '' ) {
			return undefined;
		}

		let isCurrent = true;
		const timer = setTimeout( () => {
			searchPages( search )
				.then( ( found ) => isCurrent && setPages( found ) )
				.catch( () => isCurrent && setPages( [] ) );
		}, SEARCH_DELAY );

		return () => {
			isCurrent = false;
			clearTimeout( timer );
		};
	}, [ search ] );

	const slug = textOf( settings, 'slug' );

	return (
		<ComboboxControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ __( 'Policy page', 'italy-cookie-choices' ) }
			help={ __(
				'Type to search the page, the banner links to it and does not open by itself there.',
				'italy-cookie-choices'
			) }
			value={ slug !== '' ? slug : null }
			options={ pageOptions( pages, slug ) }
			onFilterValueChange={ setSearch }
			onChange={ ( value ) => {
				if ( value === slug ) {
					return;
				}

				const page = pages.find( ( found ) => found.slug === value );
				onChange(
					page
						? { ...settings, slug: page.slug, url: page.link }
						: { ...settings, slug: '' }
				);
			} }
		/>
	);
}
