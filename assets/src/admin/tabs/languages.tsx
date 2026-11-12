/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, SelectControl, TabPanel } from '@wordpress/components';
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	draftSiteTexts,
	errorMessage,
	loadSiteTexts,
	saveSiteTexts,
} from '../api';
import { TextField, type SettingsProps } from '../fields';
import { fillEmptyTexts } from '../languages';
import type { Message } from '../message';
import type { Category, ConnectedSite, Languages, Settings } from '../settings';
import {
	AcceptButtonTextField,
	BannerTextField,
	FloatingButtonLabelField,
	PlaceholderTextFields,
} from '../texts';
import { CategoriesTab } from './categories';

interface LanguagesTabProps extends SettingsProps {
	languages: Languages;
	categories: readonly Category[];
	onMessage: ( message: Message ) => void;
}

function siteTitle( site: ConnectedSite ): string {
	return site.language === ''
		? site.name
		: `${ site.name } (${ site.language })`;
}

function errorOf( error: unknown ): Message {
	return {
		status: 'error',
		text: errorMessage(
			error,
			__( 'The texts were not filled.', 'italy-cookie-choices' )
		),
	};
}

/**
 * The translation of the policy page of a connected site, proposed while the policy link is empty:
 * the policy can change between countries, the admin chooses.
 * @param props       The props.
 * @param props.url   The proposed link.
 * @param props.onUse Uses the link.
 */
function PolicySuggestion( {
	url,
	onUse,
}: {
	url: string;
	onUse: () => void;
} ) {
	return (
		<div className="icc-admin__suggestion">
			<p>
				{ __(
					'The policy page of a connected site has a translation for this site:',
					'italy-cookie-choices'
				) }{ ' ' }
				<a href={ url } target="_blank" rel="noreferrer">
					{ url }
				</a>
			</p>
			<Button variant="secondary" onClick={ onUse }>
				{ __( 'Use this link', 'italy-cookie-choices' ) }
			</Button>
		</div>
	);
}

/**
 * The texts of a site, without the search of the policy page, that looks only in this site.
 * @param props The settings props and the categories.
 */
function SiteTextFields(
	props: SettingsProps & { categories: readonly Category[] }
) {
	return (
		<Fragment>
			<BannerTextField { ...props } />
			<TextField
				{ ...props }
				name="url"
				type="url"
				label={ __( 'Policy link', 'italy-cookie-choices' ) }
			/>
			<TextField
				{ ...props }
				name="anchor_text"
				label={ __( 'Policy link text', 'italy-cookie-choices' ) }
			/>
			<AcceptButtonTextField { ...props } />
			<FloatingButtonLabelField { ...props } />
			<PlaceholderTextFields { ...props } />
			<h3>{ __( 'Categories', 'italy-cookie-choices' ) }</h3>
			<CategoriesTab { ...props } />
		</Fragment>
	);
}

/**
 * Fills the empty texts of this site, in the form, from a connected site: they are saved with the other changes.
 * @param props The props.
 */
function ThisSiteTexts( props: LanguagesTabProps ) {
	const { languages, settings, onChange, onMessage } = props;
	const [ source, setSource ] = useState( languages.sites[ 0 ]?.id ?? 0 );
	const [ suggestion, setSuggestion ] = useState( '' );
	const [ isFilling, setFilling ] = useState( false );
	const site = languages.sites.find(
		( connected ) => connected.id === source
	);

	useEffect( () => {
		loadSiteTexts( languages.siteId )
			.then( ( loaded ) => setSuggestion( loaded.policySuggestion ) )
			.catch( () => setSuggestion( '' ) );
	}, [ languages.siteId ] );

	async function fill( translate: boolean ): Promise< void > {
		setFilling( true );
		try {
			const draft = await draftSiteTexts(
				languages.siteId,
				source,
				translate
			);
			onChange( fillEmptyTexts( settings, draft.texts ) );
			onMessage( {
				status: 'success',
				text: __(
					'The empty texts were filled: check them and save the changes.',
					'italy-cookie-choices'
				),
			} );
		} catch ( error ) {
			onMessage( errorOf( error ) );
		} finally {
			setFilling( false );
		}
	}

	return (
		<fieldset className="icc-admin__group">
			<legend>
				{ __( 'The texts of this site', 'italy-cookie-choices' ) }
			</legend>
			<p>
				{ __(
					'Fills only the empty texts, the texts already written stay. The policy link is never filled.',
					'italy-cookie-choices'
				) }
			</p>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Fill from', 'italy-cookie-choices' ) }
				value={ String( source ) }
				options={ languages.sites.map( ( connected ) => ( {
					value: String( connected.id ),
					label: siteTitle( connected ),
				} ) ) }
				onChange={ ( value ) => setSource( Number( value ) ) }
			/>
			<div className="icc-admin__buttons">
				<Button
					variant="secondary"
					disabled={ isFilling }
					onClick={ () => void fill( false ) }
				>
					{ __( 'Copy the empty texts', 'italy-cookie-choices' ) }
				</Button>
				<Button
					variant="secondary"
					disabled={ isFilling || site?.canTranslate !== true }
					onClick={ () => void fill( true ) }
				>
					{ __(
						'Translate the empty texts',
						'italy-cookie-choices'
					) }
				</Button>
			</div>
			{ suggestion !== '' && ( settings.url ?? '' ) === '' && (
				<PolicySuggestion
					url={ suggestion }
					onUse={ () => onChange( { ...settings, url: suggestion } ) }
				/>
			) }
		</fieldset>
	);
}

interface ConnectedSiteTextsProps {
	site: ConnectedSite;
	thisSiteId: number;
	categories: readonly Category[];
	onMessage: ( message: Message ) => void;
}

/**
 * The texts of a connected site, saved on that site.
 * @param props The props.
 */
function ConnectedSiteTexts( props: ConnectedSiteTextsProps ) {
	const { site, thisSiteId, categories, onMessage } = props;
	const [ texts, setTexts ] = useState< Settings | null >( null );
	const [ suggestion, setSuggestion ] = useState( '' );
	const [ isBusy, setBusy ] = useState( false );

	useEffect( () => {
		loadSiteTexts( site.id )
			.then( ( loaded ) => {
				setTexts( loaded.texts );
				setSuggestion( loaded.policySuggestion );
			} )
			.catch( ( error: unknown ) => onMessage( errorOf( error ) ) );
	}, [ site.id, onMessage ] );

	if ( texts === null ) {
		return <p>{ __( 'Loading the texts…', 'italy-cookie-choices' ) }</p>;
	}

	async function fill(
		current: Settings,
		translate: boolean
	): Promise< void > {
		setBusy( true );
		try {
			const draft = await draftSiteTexts(
				site.id,
				thisSiteId,
				translate
			);
			setTexts( fillEmptyTexts( current, draft.texts ) );
		} catch ( error ) {
			onMessage( errorOf( error ) );
		} finally {
			setBusy( false );
		}
	}

	async function save( current: Settings ): Promise< void > {
		setBusy( true );
		try {
			setTexts( ( await saveSiteTexts( site.id, current ) ).texts );
			onMessage( {
				status: 'success',
				text: sprintf(
					/* translators: %s: the name of the site. */
					__( 'The texts of %s were saved.', 'italy-cookie-choices' ),
					site.name
				),
			} );
		} catch ( error ) {
			onMessage( {
				status: 'error',
				text: errorMessage(
					error,
					__( 'The texts were not saved.', 'italy-cookie-choices' )
				),
			} );
		} finally {
			setBusy( false );
		}
	}

	return (
		<div className="icc-admin__panel">
			<div className="icc-admin__buttons">
				<Button
					variant="secondary"
					disabled={ isBusy }
					onClick={ () => void fill( texts, false ) }
				>
					{ __(
						'Copy the empty texts from this site',
						'italy-cookie-choices'
					) }
				</Button>
				<Button
					variant="secondary"
					disabled={ isBusy || ! site.canTranslate }
					onClick={ () => void fill( texts, true ) }
				>
					{ __(
						'Translate the empty texts from this site',
						'italy-cookie-choices'
					) }
				</Button>
			</div>
			<SiteTextFields
				settings={ texts }
				onChange={ setTexts }
				categories={ categories }
			/>
			{ suggestion !== '' && ( texts.url ?? '' ) === '' && (
				<PolicySuggestion
					url={ suggestion }
					onUse={ () => setTexts( { ...texts, url: suggestion } ) }
				/>
			) }
			<div>
				<Button
					variant="primary"
					isBusy={ isBusy }
					disabled={ isBusy }
					onClick={ () => void save( texts ) }
				>
					{ sprintf(
						/* translators: %s: the name of the site. */
						__( 'Save the texts of %s', 'italy-cookie-choices' ),
						site.name
					) }
				</Button>
			</div>
		</div>
	);
}

/**
 * The texts of the sites connected as translations by MultilingualPress, that the user administers.
 * @param props The props.
 */
export function LanguagesTab( props: LanguagesTabProps ) {
	const { languages, categories, onMessage } = props;

	return (
		<Fragment>
			<ThisSiteTexts { ...props } />
			<TabPanel
				className="icc-admin__languages"
				tabs={ languages.sites.map( ( site ) => ( {
					name: String( site.id ),
					title: siteTitle( site ),
				} ) ) }
			>
				{ ( tab ) => {
					const site = languages.sites.find(
						( connected ) => String( connected.id ) === tab.name
					);

					return (
						site && (
							<ConnectedSiteTexts
								key={ site.id }
								site={ site }
								thisSiteId={ languages.siteId }
								categories={ categories }
								onMessage={ onMessage }
							/>
						)
					);
				} }
			</TabPanel>
		</Fragment>
	);
}
