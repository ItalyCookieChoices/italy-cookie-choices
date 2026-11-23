/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, Notice } from '@wordpress/components';
import { createElement, Fragment, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { errorMessage, frameScanUrl } from './api';
import {
	durationText,
	emptyCookieReport,
	ownerOf,
	withPageCookies,
	withPageError,
	type CookieOwner,
	type CookieScanReport,
	type FoundCookie,
} from './cookie-scan';
import type { SettingsProps } from './fields';
import {
	FrameScanError,
	loadsAsAnonymousVisitor,
	scanInFrame,
} from './frame-scan';
import { pagesCount, type ScanTarget } from './scan';
import type { Vendor } from './settings';

interface CookieScanPanelProps extends SettingsProps {
	vendors: readonly Vendor[];
	/** The pages of the selection of the scan panel. */
	targets: () => Promise< ScanTarget[] >;
	/** The category of the site cookies added from the scan. */
	siteCookieCategory: string;
	disabled: boolean;
}

function failureMessage( failure: unknown ): string {
	if ( ! ( failure instanceof FrameScanError ) ) {
		return errorMessage(
			failure,
			__( 'The scan of the page failed.', 'italy-cookie-choices' )
		);
	}

	switch ( failure.reason ) {
		case 'refused':
			return __(
				'The page refused to open in a frame, a security plugin or the server may send X-Frame-Options.',
				'italy-cookie-choices'
			);
		case 'not-in-scan-mode':
			return __(
				'The page did not open in scan mode, a cache may serve it: exclude the addresses with icc_scan from the cache.',
				'italy-cookie-choices'
			);
		default:
			return __(
				'The page did not load in time.',
				'italy-cookie-choices'
			);
	}
}

function ownerText( owner: CookieOwner ): string {
	switch ( owner.kind ) {
		case 'vendor':
			return sprintf(
				/* translators: %s: the name of the vendor. */
				__( 'A cookie of %s', 'italy-cookie-choices' ),
				owner.name
			);
		case 'site':
			return __( 'A site cookie', 'italy-cookie-choices' );
		default:
			return __( 'Unknown', 'italy-cookie-choices' );
	}
}

/**
 * The scan of the cookies in the browser: the chosen pages are loaded in a hidden frame, in scan mode,
 * with every category accepted, and the panel lists the cookies they set. The unknown ones can be added
 * as site cookies, saved with the other settings.
 * @param props The props.
 */
export function CookieScanPanel( props: CookieScanPanelProps ) {
	const { settings, onChange, vendors, siteCookieCategory } = props;
	const frames = useRef< HTMLDivElement >( null );
	const [ report, setReport ] = useState< CookieScanReport | null >( null );
	const [ total, setTotal ] = useState( 0 );
	const [ isScanning, setScanning ] = useState( false );
	const [ error, setError ] = useState( '' );
	const siteCookies = settings.site_cookies ?? [];

	async function scanPage(
		merged: CookieScanReport,
		target: ScanTarget,
		container: HTMLElement
	): Promise< CookieScanReport > {
		try {
			const cookies = await scanInFrame(
				await frameScanUrl( target.url ),
				container
			);
			return withPageCookies( merged, target.url, cookies );
		} catch ( failure ) {
			return withPageError(
				merged,
				target.url,
				failureMessage( failure )
			);
		}
	}

	async function scan(): Promise< void > {
		const container = frames.current;
		if ( container === null ) {
			return;
		}

		setScanning( true );
		setError( '' );
		setReport( null );
		try {
			const targets = await props.targets();
			setTotal( targets.length );
			let merged = emptyCookieReport();
			setReport( merged );
			for ( const target of targets ) {
				merged = await scanPage( merged, target, container );
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

	function addSiteCookie( cookie: FoundCookie ): void {
		onChange( {
			...settings,
			site_cookies: [
				...siteCookies,
				{
					name: cookie.name,
					category: siteCookieCategory,
					duration: durationText( cookie.duration ),
					purpose: '',
				},
			],
		} );
	}

	const found = Object.values( report?.cookies ?? {} ).map( ( cookie ) => ( {
		cookie,
		owner: ownerOf(
			cookie.name,
			vendors,
			settings.custom_vendors ?? [],
			siteCookies
		),
	} ) );

	return (
		<Fragment>
			<h4>{ __( 'Cookies in the browser', 'italy-cookie-choices' ) }</h4>
			<p>
				{ __(
					'Loads the chosen pages in a hidden frame with every category accepted, and lists the cookies they set while they load, with their duration. No consent is saved. The cookies set by the frames of other sites come from the vendors.',
					'italy-cookie-choices'
				) }
			</p>
			{ ! loadsAsAnonymousVisitor() && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'This browser loads the pages as the logged-in administrator, and some plugins do not track the administrators. Use Chrome or Edge to scan as an anonymous visitor.',
						'italy-cookie-choices'
					) }
				</Notice>
			) }
			<div>
				<Button
					variant="secondary"
					isBusy={ isScanning }
					disabled={ isScanning || props.disabled }
					onClick={ () => void scan() }
				>
					{ __(
						'Scan the cookies in the browser',
						'italy-cookie-choices'
					) }
				</Button>
			</div>
			<div ref={ frames } />
			{ error !== '' && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			{ report !== null && (
				<Fragment>
					<p aria-live="polite">
						{ sprintf(
							/* translators: 1: the pages scanned, 2: the pages to scan. */
							__(
								'Scanned %1$d of %2$d pages.',
								'italy-cookie-choices'
							),
							report.scanned,
							total
						) }
					</p>
					{ found.map( ( { cookie, owner } ) => (
						<div className="icc-admin__found" key={ cookie.name }>
							<code>{ cookie.name }</code>
							<span>{ durationText( cookie.duration ) }</span>
							<span>{ pagesCount( cookie.pages ) }</span>
							<span>{ ownerText( owner ) }</span>
							{ owner.kind === 'unknown' && (
								<Button
									variant="secondary"
									aria-label={ sprintf(
										/* translators: %s: the cookie name. */
										__(
											'Add %s as a site cookie',
											'italy-cookie-choices'
										),
										cookie.name
									) }
									onClick={ () => addSiteCookie( cookie ) }
								>
									{ __(
										'Add as a site cookie',
										'italy-cookie-choices'
									) }
								</Button>
							) }
						</div>
					) ) }
					{ report.scanned > 0 && found.length === 0 && (
						<p>
							{ __(
								'The pages set no cookie.',
								'italy-cookie-choices'
							) }
						</p>
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
		</Fragment>
	);
}
