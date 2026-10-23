/**
 * The shape of the option saved through /wp/v2/settings, see ItalyCookieChoices\Settings\Schema.
 *
 * A checkbox is on when its key has the '1' value, it is off when the key is missing.
 */
export type Checkbox =
	| 'active'
	| 'reload'
	| 'html_margin'
	| 'target'
	| 'nofollow'
	| 'all_block'
	| 'consent_log';

export type Text =
	| 'text'
	| 'url'
	| 'slug'
	| 'anchor_text'
	| 'button_text'
	| 'banner_bg'
	| 'banner_text_color'
	| 'customCSS'
	| 'bannerStyle'
	| 'contentStyle'
	| 'consentText'
	| 'infoClass'
	| 'closeClass'
	| 'cookie_name'
	| 'cookie_value'
	| 'content_message_text'
	| 'content_message_button_text'
	| 'excluded_pages'
	| 'banner'
	| 'js_template'
	| 'consent_log_retention';

export interface CategoryTexts {
	label?: string;
	description?: string;
}

export interface CustomVendor {
	id: string;
	name: string;
	category: string;
	patterns: string[];
}

export type Settings = Partial< Record< Checkbox, '1' > > &
	Partial< Record< Text, string > > & {
		categories?: Record< string, CategoryTexts >;
		disabled_vendors?: string[];
		custom_vendors?: CustomVendor[];
		allowed_patterns?: string[];
	};

/**
 * The data the settings page cannot read from the REST API, printed by
 * ItalyCookieChoices\Settings\UI\SettingsPage.
 */
export interface Category {
	id: string;
	required: boolean;
	label: string;
	description: string;
}

export interface Vendor {
	id: string;
	name: string;
	category: string;
}

export interface AdminData {
	categories: Category[];
	vendors: Vendor[];
	consentLogExportUrl: string;
	documentationUrl: string;
}

export const SCHEMA_VERSION = 5;

function isRecord( value: unknown ): value is Record< string, unknown > {
	return (
		typeof value === 'object' && value !== null && ! Array.isArray( value )
	);
}

function categoryFrom( value: unknown ): Category | null {
	if ( ! isRecord( value ) || typeof value.id !== 'string' ) {
		return null;
	}

	return {
		id: value.id,
		required: value.required === true,
		label: typeof value.label === 'string' ? value.label : value.id,
		description:
			typeof value.description === 'string' ? value.description : '',
	};
}

function vendorFrom( value: unknown ): Vendor | null {
	if (
		! isRecord( value ) ||
		typeof value.id !== 'string' ||
		typeof value.category !== 'string'
	) {
		return null;
	}

	return {
		id: value.id,
		name: typeof value.name === 'string' ? value.name : value.id,
		category: value.category,
	};
}

/**
 * Reads the data printed in the page, the invalid values are skipped.
 * @param raw The value of window.italyCookieChoicesAdmin.
 */
export function parseAdminData( raw: unknown ): AdminData {
	const data = isRecord( raw ) ? raw : {};
	const categories = Array.isArray( data.categories )
		? data.categories.map( categoryFrom )
		: [];
	const vendors = Array.isArray( data.vendors )
		? data.vendors.map( vendorFrom )
		: [];

	return {
		categories: categories.filter(
			( category ): category is Category => category !== null
		),
		vendors: vendors.filter(
			( vendor ): vendor is Vendor => vendor !== null
		),
		consentLogExportUrl:
			typeof data.consentLogExportUrl === 'string'
				? data.consentLogExportUrl
				: '',
		documentationUrl:
			typeof data.documentationUrl === 'string'
				? data.documentationUrl
				: '',
	};
}

/**
 * The option read from the REST API, PHP sends an empty object as an empty list.
 * @param raw The italy_cookie_choices value of /wp/v2/settings, null when it is not valid.
 */
export function normalizeSettings( raw: unknown ): Settings {
	if ( ! isRecord( raw ) ) {
		return {};
	}

	const settings: Settings = { ...( raw as Settings ) };
	if ( ! isRecord( raw.categories ) ) {
		delete settings.categories;
	}

	return settings;
}

export function isChecked( settings: Settings, name: Checkbox ): boolean {
	return settings[ name ] === '1';
}

export function withCheckbox(
	settings: Settings,
	name: Checkbox,
	checked: boolean
): Settings {
	const changed = { ...settings };
	if ( checked ) {
		changed[ name ] = '1';
		return changed;
	}

	delete changed[ name ];
	return changed;
}

export function textOf( settings: Settings, name: Text ): string {
	return settings[ name ] ?? '';
}

export function withCategoryText(
	settings: Settings,
	category: string,
	field: keyof CategoryTexts,
	text: string
): Settings {
	const categories = settings.categories ?? {};

	return {
		...settings,
		categories: {
			...categories,
			[ category ]: { ...categories[ category ], [ field ]: text },
		},
	};
}

export function isVendorBlocked( settings: Settings, id: string ): boolean {
	return ! ( settings.disabled_vendors ?? [] ).includes( id );
}

export function withVendorBlocked(
	settings: Settings,
	id: string,
	blocked: boolean
): Settings {
	const disabled = ( settings.disabled_vendors ?? [] ).filter(
		( disabledId ) => disabledId !== id
	);

	return {
		...settings,
		disabled_vendors: blocked ? disabled : [ ...disabled, id ],
	};
}

/**
 * A new custom vendor with an id not taken by the others.
 * @param vendors  The saved custom vendors.
 * @param category The category of the new vendor.
 */
export function newCustomVendor(
	vendors: readonly CustomVendor[],
	category: string
): CustomVendor {
	const ids = vendors.map( ( vendor ) => vendor.id );
	let number = vendors.length + 1;
	while ( ids.includes( `custom-${ number }` ) ) {
		number++;
	}

	return { id: `custom-${ number }`, name: '', category, patterns: [] };
}

export function withCustomVendor(
	settings: Settings,
	index: number,
	vendor: CustomVendor | null
): Settings {
	const vendors = [ ...( settings.custom_vendors ?? [] ) ];
	if ( vendor === null ) {
		vendors.splice( index, 1 );
		return { ...settings, custom_vendors: vendors };
	}

	vendors[ index ] = vendor;
	return { ...settings, custom_vendors: vendors };
}
