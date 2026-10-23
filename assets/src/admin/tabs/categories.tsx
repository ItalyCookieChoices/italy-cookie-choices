/* @jsxRuntime classic */
/* @jsx createElement */
import { TextareaControl, TextControl } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { SettingsProps } from '../fields';
import { withCategoryText, type Category } from '../settings';

/**
 * The names and the descriptions of the categories in the preferences, empty for the default ones.
 * @param props            The settings props.
 * @param props.settings   The settings.
 * @param props.onChange   Changes the settings.
 * @param props.categories The categories with their default texts.
 */
export function CategoriesTab( {
	settings,
	onChange,
	categories,
}: SettingsProps & { categories: readonly Category[] } ) {
	return (
		<Fragment>
			<p>
				{ __(
					'Leave a field empty to use the default text, translated in the language of the site.',
					'italy-cookie-choices'
				) }
			</p>
			{ categories.map( ( category ) => {
				const texts = settings.categories?.[ category.id ] ?? {};

				return (
					<fieldset key={ category.id } className="icc-admin__group">
						<legend>{ category.label }</legend>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Name', 'italy-cookie-choices' ) }
							placeholder={ category.label }
							value={ texts.label ?? '' }
							onChange={ ( label: string ) =>
								onChange(
									withCategoryText(
										settings,
										category.id,
										'label',
										label
									)
								)
							}
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __(
								'Description',
								'italy-cookie-choices'
							) }
							placeholder={ category.description }
							value={ texts.description ?? '' }
							onChange={ ( description: string ) =>
								onChange(
									withCategoryText(
										settings,
										category.id,
										'description',
										description
									)
								)
							}
						/>
					</fieldset>
				);
			} ) }
		</Fragment>
	);
}
