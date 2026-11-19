/* @jsxRuntime classic */
/* @jsx createElement */
import { ComboboxControl } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { searchPages, type Page } from './api';
import type { SettingsProps } from './fields';
import { LINK_SETTINGS, textOf, type BannerLink } from './settings';

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

function labelsOf( link: BannerLink ): { label: string; help: string } {
	if ( link === 'legal-notice' ) {
		return {
			label: __( 'Legal notice page', 'italy-cookie-choices' ),
			help: __(
				'Type to search the page of the legal notice, the banner links to it next to the policy and does not open by itself there.',
				'italy-cookie-choices'
			),
		};
	}

	return {
		label: __( 'Policy page', 'italy-cookie-choices' ),
		help: __(
			'Type to search the page, the banner links to it and does not open by itself there.',
			'italy-cookie-choices'
		),
	};
}

/**
 * The page of a link of the banner, searched only when the site owner types (#154).
 * Choosing it fills the link, the banner does not open by itself on that page.
 * @param props          The settings props and the link.
 * @param props.settings The settings.
 * @param props.onChange Changes the settings.
 * @param props.link     The link of the banner.
 */
export function LinkPage( {
	settings,
	onChange,
	link,
}: SettingsProps & { link: BannerLink } ) {
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

	const names = LINK_SETTINGS[ link ];
	const slug = textOf( settings, names.page );
	const { label, help } = labelsOf( link );

	return (
		<ComboboxControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ label }
			help={ help }
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
						? {
								...settings,
								[ names.page ]: page.slug,
								[ names.url ]: page.link,
							}
						: { ...settings, [ names.page ]: '' }
				);
			} }
		/>
	);
}
