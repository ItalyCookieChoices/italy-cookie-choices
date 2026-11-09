/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { Category, VendorCookie } from './settings';

interface CookieListProps< Item extends VendorCookie > {
	cookies: readonly Item[];
	/** The label of the button that adds a cookie. */
	addLabel: string;
	/** The categories a cookie can be in, none for the cookies of a vendor, that take its category. */
	categories?: readonly Category[];
	newCookie: () => Item;
	onChange: ( cookies: Item[] ) => void;
}

function replaced< Item >(
	items: readonly Item[],
	index: number,
	item: Item
): Item[] {
	return items.map( ( current, position ) =>
		position === index ? item : current
	);
}

/**
 * The cookies of a vendor or of the site, with their name, duration and purpose, for the cookie table.
 * @param props            The list props.
 * @param props.cookies    The cookies.
 * @param props.addLabel   The label of the button that adds a cookie.
 * @param props.categories The categories a cookie can be in.
 * @param props.newCookie  Creates an empty cookie.
 * @param props.onChange   Saves the changed cookies.
 */
export function CookieList< Item extends VendorCookie >( {
	cookies,
	addLabel,
	categories,
	newCookie,
	onChange,
}: CookieListProps< Item > ) {
	return (
		<div className="icc-admin__cookies">
			{ cookies.map( ( cookie, index ) => (
				<div className="icc-admin__cookie" key={ index }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Cookie name', 'italy-cookie-choices' ) }
						help={ __(
							'Add * for a prefix, like _ga_*.',
							'italy-cookie-choices'
						) }
						value={ cookie.name }
						onChange={ ( name: string ) =>
							onChange(
								replaced( cookies, index, { ...cookie, name } )
							)
						}
					/>
					{ categories !== undefined && 'category' in cookie && (
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __(
								'Cookie category',
								'italy-cookie-choices'
							) }
							value={ String( cookie.category ) }
							options={ categories.map( ( category ) => ( {
								value: category.id,
								label: category.label,
							} ) ) }
							onChange={ ( category: string ) =>
								onChange(
									replaced( cookies, index, {
										...cookie,
										category,
									} )
								)
							}
						/>
					) }
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Duration', 'italy-cookie-choices' ) }
						placeholder={ __( '1 year', 'italy-cookie-choices' ) }
						value={ cookie.duration }
						onChange={ ( duration: string ) =>
							onChange(
								replaced( cookies, index, {
									...cookie,
									duration,
								} )
							)
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Purpose', 'italy-cookie-choices' ) }
						value={ cookie.purpose }
						onChange={ ( purpose: string ) =>
							onChange(
								replaced( cookies, index, {
									...cookie,
									purpose,
								} )
							)
						}
					/>
					<Button
						variant="tertiary"
						isDestructive
						onClick={ () =>
							onChange(
								cookies.filter(
									( _, position ) => position !== index
								)
							)
						}
					>
						{ sprintf(
							/* translators: %s: the cookie name. */
							__(
								'Remove the cookie %s',
								'italy-cookie-choices'
							),
							cookie.name
						) }
					</Button>
				</div>
			) ) }
			<Button
				variant="secondary"
				onClick={ () => onChange( [ ...cookies, newCookie() ] ) }
			>
				{ addLabel }
			</Button>
		</div>
	);
}
