/* @jsxRuntime classic */
/* @jsx createElement */
import {
	Button,
	CheckboxControl,
	FormTokenField,
	Notice,
} from '@wordpress/components';
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	errorMessage,
	scanPages,
	scanTargets,
	searchCategories,
	searchPosts,
	type Term,
} from './api';
import type { SettingsProps } from './fields';
import {
	batches,
	emptyReport,
	mergeScan,
	pagesCount,
	parseScanSchedule,
	type ScanReport,
	type ScanSchedule,
	type ScanSelection,
} from './scan';
import { CookieScanPanel } from './cookie-scan-panel';
import { ScanScheduleFields } from './scan-schedule';
import {
	isVendorBlocked,
	newCustomVendor,
	withVendorBlocked,
	type PostType,
	type Vendor,
} from './settings';

const SEARCH_DELAY = 300;

interface ScanPanelProps extends SettingsProps {
	vendors: readonly Vendor[];
	postTypes: readonly PostType[];
	/** The category of the custom vendors added from the scan. */
	customCategory: string;
	/** The category of the site cookies added from the scan of the cookies. */
	siteCookieCategory: string;
	/** The schedule of the scan, its pages are the first selection of the panel. */
	schedule?: ScanSchedule;
}

interface SearchFieldProps {
	label: string;
	value: Term[];
	onChange: ( terms: Term[] ) => void;
	search: ( text: string ) => Promise< Term[] >;
}

/**
 * The posts or the categories found by the search, typed in a token field: nothing loads before the search.
 * @param props          The props.
 * @param props.label
 * @param props.value
 * @param props.onChange
 * @param props.search
 */
function SearchField( {
	label,
	value,
	onChange,
	search: find,
}: SearchFieldProps ) {
	const [ search, setSearch ] = useState( '' );
	const [ found, setFound ] = useState< Term[] >( [] );

	useEffect( () => {
		if ( search.trim() === '' ) {
			return undefined;
		}

		let isCurrent = true;
		const timer = setTimeout( () => {
			find( search )
				.then( ( terms ) => isCurrent && setFound( terms ) )
				.catch( () => isCurrent && setFound( [] ) );
		}, SEARCH_DELAY );

		return () => {
			isCurrent = false;
			clearTimeout( timer );
		};
	}, [ search, find ] );

	const known = [ ...value, ...found ];

	return (
		<FormTokenField
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ label }
			value={ value.map( ( term ) => term.name ) }
			suggestions={ found.map( ( term ) => term.name ) }
			onInputChange={ setSearch }
			onChange={ ( names ) =>
				onChange(
					names
						.map( ( name ) =>
							known.find(
								( term ) =>
									term.name ===
									( typeof name === 'string'
										? name
										: name.value )
							)
						)
						.filter( ( term ): term is Term => term !== undefined )
				)
			}
		/>
	);
}

/**
 * The scan of the site: the site owner chooses the pages, the server reads them in batches
 * and the panel lists the vendors and the unknown third parties found. Nothing changes without the site owner,
 * the changes are saved with the other settings.
 * @param props The props.
 */
export function ScanPanel( props: ScanPanelProps ) {
	const { settings, onChange, vendors, customCategory } = props;
	const availableTypes = props.postTypes;
	const schedule = props.schedule ?? parseScanSchedule( undefined );
	const [ home, setHome ] = useState( schedule.home );
	const [ postTypes, setPostTypes ] = useState< string[] >(
		schedule.postTypes
	);
	const [ categories, setCategories ] = useState< Term[] >(
		schedule.categories
	);
	const [ posts, setPosts ] = useState< Term[] >( schedule.posts );
	const [ report, setReport ] = useState< ScanReport | null >( null );
	const [ total, setTotal ] = useState( 0 );
	const [ isTruncated, setTruncated ] = useState( false );
	const [ isScanning, setScanning ] = useState( false );
	const [ error, setError ] = useState( '' );

	const isEmpty =
		! home &&
		postTypes.length === 0 &&
		categories.length === 0 &&
		posts.length === 0;
	const customVendors = settings.custom_vendors ?? [];
	const selection: ScanSelection = {
		home,
		postTypes,
		categories: categories.map( ( category ) => category.id ),
		posts: posts.map( ( post ) => post.id ),
	};

	async function scan(): Promise< void > {
		setScanning( true );
		setError( '' );
		setReport( null );
		try {
			const found = await scanTargets( selection );
			setTotal( found.targets.length );
			setTruncated( found.truncated );
			let merged = emptyReport();
			setReport( merged );
			for ( const batch of batches( found.targets ) ) {
				merged = mergeScan( merged, await scanPages( batch ) );
				setReport( merged );
			}
		} catch ( failure ) {
			setError(
				errorMessage(
					failure,
					__( 'The scan failed.', 'italy-cookie-choices' )
				)
			);
		} finally {
			setScanning( false );
		}
	}

	function vendorName( id: string ): string {
		return (
			vendors.find( ( vendor ) => vendor.id === id )?.name ??
			customVendors.find( ( vendor ) => vendor.id === id )?.name ??
			id
		);
	}

	function isPreset( id: string ): boolean {
		return vendors.some( ( vendor ) => vendor.id === id );
	}

	function isCustomPattern( host: string ): boolean {
		return customVendors.some( ( vendor ) =>
			vendor.patterns.includes( host )
		);
	}

	function addCustomVendor( host: string ): void {
		onChange( {
			...settings,
			custom_vendors: [
				...customVendors,
				{
					...newCustomVendor( customVendors, customCategory ),
					name: host,
					patterns: [ host ],
				},
			],
		} );
	}

	return (
		<fieldset className="icc-admin__group">
			<legend>{ __( 'Scan the site', 'italy-cookie-choices' ) }</legend>
			<p>
				{ __(
					'Reads the chosen pages from the server and finds the vendors of their scripts, frames and tracking pixels. The scan of the cookies below finds the cookies the pages set in the browser.',
					'italy-cookie-choices'
				) }
			</p>
			<CheckboxControl
				__nextHasNoMarginBottom
				label={ __( 'Home page', 'italy-cookie-choices' ) }
				checked={ home }
				onChange={ setHome }
			/>
			{ availableTypes.map( ( type ) => (
				<CheckboxControl
					__nextHasNoMarginBottom
					key={ type.slug }
					label={ type.name }
					checked={ postTypes.includes( type.slug ) }
					onChange={ ( checked: boolean ) =>
						setPostTypes(
							checked
								? [ ...postTypes, type.slug ]
								: postTypes.filter(
										( slug ) => slug !== type.slug
									)
						)
					}
				/>
			) ) }
			<SearchField
				label={ __( 'Categories', 'italy-cookie-choices' ) }
				value={ categories }
				onChange={ setCategories }
				search={ searchCategories }
			/>
			<SearchField
				label={ __( 'Posts and pages', 'italy-cookie-choices' ) }
				value={ posts }
				onChange={ setPosts }
				search={ searchPosts }
			/>
			<div>
				<Button
					variant="secondary"
					isBusy={ isScanning }
					disabled={ isScanning || isEmpty }
					onClick={ () => void scan() }
				>
					{ __( 'Scan the site', 'italy-cookie-choices' ) }
				</Button>
			</div>
			<ScanScheduleFields
				schedule={ schedule }
				selection={ selection }
				isEmpty={ isEmpty }
			/>
			{ error !== '' && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			{ report !== null && (
				<Fragment>
					<p aria-live="polite">
						{ total === 0
							? __(
									'There are no published pages to scan in the selection.',
									'italy-cookie-choices'
								)
							: sprintf(
									/* translators: 1: the pages scanned, 2: the pages to scan. */
									__(
										'Scanned %1$d of %2$d pages.',
										'italy-cookie-choices'
									),
									report.scanned,
									total
								) }
					</p>
					{ isTruncated && (
						<p>
							{ __(
								'The selection has more pages than the limit, only the first ones are scanned.',
								'italy-cookie-choices'
							) }
						</p>
					) }
					{ Object.keys( report.vendors ).length > 0 && (
						<Fragment>
							<h4>
								{ __(
									'Vendors found',
									'italy-cookie-choices'
								) }
							</h4>
							{ Object.entries( report.vendors ).map(
								( [ id, pages ] ) =>
									isPreset( id ) ? (
										<CheckboxControl
											__nextHasNoMarginBottom
											key={ id }
											label={ sprintf(
												/* translators: %s: the name of the vendor. */
												__(
													'Block %s',
													'italy-cookie-choices'
												),
												vendorName( id )
											) }
											help={ pagesCount( pages ) }
											checked={ isVendorBlocked(
												settings,
												id
											) }
											onChange={ ( checked: boolean ) =>
												onChange(
													withVendorBlocked(
														settings,
														id,
														checked
													)
												)
											}
										/>
									) : (
										<p key={ id }>
											{ sprintf(
												/* translators: 1: the name of the vendor, 2: the number of pages. */
												__(
													'%1$s, a custom vendor, blocked: %2$s',
													'italy-cookie-choices'
												),
												vendorName( id ),
												pagesCount( pages )
											) }
										</p>
									)
							) }
						</Fragment>
					) }
					{ Object.keys( report.hosts ).length > 0 && (
						<Fragment>
							<h4>
								{ __(
									'Unknown third parties',
									'italy-cookie-choices'
								) }
							</h4>
							<p>
								{ __(
									'Their scripts and frames are not blocked. Add them as custom vendors when they set cookies: some, like the libraries served by a CDN, do not.',
									'italy-cookie-choices'
								) }
							</p>
							{ Object.entries( report.hosts ).map(
								( [ host, pages ] ) => (
									<div
										className="icc-admin__found"
										key={ host }
									>
										<code>{ host }</code>
										<span>{ pagesCount( pages ) }</span>
										<Button
											variant="secondary"
											disabled={ isCustomPattern( host ) }
											aria-label={ sprintf(
												/* translators: %s: the host of the third party. */
												__(
													'Add %s as a custom vendor',
													'italy-cookie-choices'
												),
												host
											) }
											onClick={ () =>
												addCustomVendor( host )
											}
										>
											{ __(
												'Add as a custom vendor',
												'italy-cookie-choices'
											) }
										</Button>
									</div>
								)
							) }
						</Fragment>
					) }
					{ report.errors.map( ( failure ) => (
						<Notice
							key={ failure.url }
							status="warning"
							isDismissible={ false }
						>
							{ `${ failure.url }: ${ failure.error }` }
						</Notice>
					) ) }
				</Fragment>
			) }
			<CookieScanPanel
				settings={ settings }
				onChange={ onChange }
				vendors={ vendors }
				targets={ async () =>
					( await scanTargets( selection ) ).targets
				}
				siteCookieCategory={ props.siteCookieCategory }
				disabled={ isScanning || isEmpty }
			/>
		</fieldset>
	);
}
