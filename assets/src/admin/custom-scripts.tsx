/* @jsxRuntime classic */
/* @jsx createElement */
import {
	Button,
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { Category, CustomScript } from './settings';

interface CustomScriptListProps {
	scripts: readonly CustomScript[];
	categories: readonly Category[];
	/** False for the users who cannot save unfiltered HTML: they only see the names. */
	canEdit: boolean;
	onChange: ( scripts: CustomScript[] ) => void;
}

function replaced(
	scripts: readonly CustomScript[],
	index: number,
	script: CustomScript
): CustomScript[] {
	return scripts.map( ( current, position ) =>
		position === index ? script : current
	);
}

function isPosition( value: string ): value is CustomScript[ 'position' ] {
	return value === 'head' || value === 'body';
}

/**
 * The code the plugin adds to the pages after the consent to its category, like a chat or a tracking snippet.
 * @param props            The list props.
 * @param props.scripts    The scripts.
 * @param props.categories The categories a script can wait for.
 * @param props.canEdit    Whether the user can save unfiltered HTML.
 * @param props.onChange   Saves the changed scripts.
 */
export function CustomScriptList( {
	scripts,
	categories,
	canEdit,
	onChange,
}: CustomScriptListProps ) {
	if ( ! canEdit ) {
		return (
			<div className="icc-admin__scripts">
				<p>
					{ __(
						'Only the administrators who can save unfiltered HTML change the scripts.',
						'italy-cookie-choices'
					) }
				</p>
				<ul>
					{ scripts.map( ( script, index ) => (
						<li key={ index }>{ script.name }</li>
					) ) }
				</ul>
			</div>
		);
	}

	return (
		<div className="icc-admin__scripts">
			{ scripts.map( ( script, index ) => (
				<div className="icc-admin__script" key={ index }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Script name', 'italy-cookie-choices' ) }
						value={ script.name }
						onChange={ ( name: string ) =>
							onChange(
								replaced( scripts, index, { ...script, name } )
							)
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Category', 'italy-cookie-choices' ) }
						help={ __(
							'The code runs after the consent to this category, the necessary one runs on every page.',
							'italy-cookie-choices'
						) }
						value={ script.category }
						options={ categories.map( ( category ) => ( {
							value: category.id,
							label: category.label,
						} ) ) }
						onChange={ ( category: string ) =>
							onChange(
								replaced( scripts, index, {
									...script,
									category,
								} )
							)
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Position', 'italy-cookie-choices' ) }
						value={ script.position }
						options={ [
							{
								value: 'head',
								label: __(
									'In the head',
									'italy-cookie-choices'
								),
							},
							{
								value: 'body',
								label: __(
									'At the end of the body',
									'italy-cookie-choices'
								),
							},
						] }
						onChange={ ( position: string ) =>
							onChange(
								replaced( scripts, index, {
									...script,
									position: isPosition( position )
										? position
										: 'body',
								} )
							)
						}
					/>
					<TextareaControl
						__nextHasNoMarginBottom
						className="icc-admin__code"
						label={ __( 'Code', 'italy-cookie-choices' ) }
						help={ __(
							'HTML: the scripts and the elements they need, like the image of a pixel. Nothing of it loads before the consent.',
							'italy-cookie-choices'
						) }
						rows={ 6 }
						value={ script.code }
						onChange={ ( code: string ) =>
							onChange(
								replaced( scripts, index, { ...script, code } )
							)
						}
					/>
					<Button
						variant="tertiary"
						isDestructive
						onClick={ () =>
							onChange(
								scripts.filter(
									( _, position ) => position !== index
								)
							)
						}
					>
						{ sprintf(
							/* translators: %s: the script name. */
							__(
								'Remove the script %s',
								'italy-cookie-choices'
							),
							script.name
						) }
					</Button>
				</div>
			) ) }
			<Button
				variant="secondary"
				onClick={ () =>
					onChange( [
						...scripts,
						{
							name: '',
							category:
								categories[ categories.length - 1 ]?.id ?? '',
							position: 'body',
							code: '',
						},
					] )
				}
			>
				{ __( 'Add a script', 'italy-cookie-choices' ) }
			</Button>
		</div>
	);
}
