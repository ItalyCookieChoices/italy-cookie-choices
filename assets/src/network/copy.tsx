/* @jsxRuntime classic */
/* @jsx createElement */
import {
	Button,
	CheckboxControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { errorMessage } from '../admin/api';
import { copySettings } from './api';
import type { Message } from './app';
import type { Site } from './data';

interface CopyPanelProps {
	shared: boolean;
	mainSiteId: number;
	sites: Site[];
	onMessage: ( message: Message ) => void;
}

function copiedMessage( count: number ): string {
	return sprintf(
		/* translators: %d: the number of sites. */
		_n(
			'The settings were copied to %d site.',
			'The settings were copied to %d sites.',
			count,
			'italy-cookie-choices'
		),
		count
	);
}

/**
 * Copies the settings of a site to other sites, once: no link between the sites is stored.
 * @param props The props.
 */
export function CopyPanel( props: CopyPanelProps ) {
	const { shared, mainSiteId, sites, onMessage } = props;
	const [ source, setSource ] = useState( mainSiteId );
	const [ targets, setTargets ] = useState< number[] >( [] );
	const [ withTexts, setWithTexts ] = useState( false );
	const [ isCopying, setCopying ] = useState( false );
	const [ isConfirming, setConfirming ] = useState( false );

	if ( shared ) {
		return (
			<fieldset className="icc-network__group">
				<legend>
					{ __( 'Copy the settings', 'italy-cookie-choices' ) }
				</legend>
				<p>
					{ __(
						'The sites use the settings of the main site, there is nothing to copy.',
						'italy-cookie-choices'
					) }
				</p>
			</fieldset>
		);
	}

	const others = sites.filter( ( site ) => site.id !== source );
	const chosen = targets.filter( ( id ) => id !== source );

	function toggle( id: number, checked: boolean ): void {
		setTargets( ( current ) =>
			checked
				? [ ...current, id ]
				: current.filter( ( target ) => target !== id )
		);
	}

	async function copy(): Promise< void > {
		setConfirming( false );
		setCopying( true );
		try {
			const copied = await copySettings( source, chosen, withTexts );
			onMessage( {
				status: 'success',
				text: copiedMessage( copied.length ),
			} );
			setTargets( [] );
		} catch ( error ) {
			onMessage( {
				status: 'error',
				text: errorMessage(
					error,
					__(
						'The settings were not copied.',
						'italy-cookie-choices'
					)
				),
			} );
		} finally {
			setCopying( false );
		}
	}

	return (
		<fieldset className="icc-network__group">
			<legend>
				{ __( 'Copy the settings', 'italy-cookie-choices' ) }
			</legend>
			<p>
				{ __(
					'Replaces the settings of the chosen sites with the ones of another site. The logo, the pages where the banner does not open, the cookie domain and the consent log of each site are never copied.',
					'italy-cookie-choices'
				) }
			</p>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Copy from', 'italy-cookie-choices' ) }
				value={ String( source ) }
				options={ sites.map( ( site ) => ( {
					value: String( site.id ),
					label: `${ site.name } (${ site.url })`,
				} ) ) }
				onChange={ ( value ) => setSource( Number( value ) ) }
			/>
			<div
				role="group"
				aria-label={ __( 'Copy to', 'italy-cookie-choices' ) }
				className="icc-network__sites"
			>
				<p className="icc-network__label">
					{ __( 'Copy to', 'italy-cookie-choices' ) }
				</p>
				{ others.map( ( site ) => (
					<CheckboxControl
						key={ site.id }
						__nextHasNoMarginBottom
						label={ site.name }
						help={ site.url }
						checked={ chosen.includes( site.id ) }
						onChange={ ( checked ) => toggle( site.id, checked ) }
					/>
				) ) }
			</div>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Copy the texts too', 'italy-cookie-choices' ) }
				help={ __(
					'Off: each site keeps its own texts, for its language.',
					'italy-cookie-choices'
				) }
				checked={ withTexts }
				onChange={ setWithTexts }
			/>
			{ isConfirming ? (
				<div className="icc-network__confirm" role="alert">
					<p>
						{ sprintf(
							/* translators: %d: the number of sites. */
							_n(
								'The settings of %d site will be replaced.',
								'The settings of %d sites will be replaced.',
								chosen.length,
								'italy-cookie-choices'
							),
							chosen.length
						) }
					</p>
					<Button variant="primary" onClick={ () => void copy() }>
						{ __( 'Replace the settings', 'italy-cookie-choices' ) }
					</Button>
					<Button
						variant="tertiary"
						onClick={ () => setConfirming( false ) }
					>
						{ __( 'Cancel', 'italy-cookie-choices' ) }
					</Button>
				</div>
			) : (
				<div>
					<Button
						variant="primary"
						isBusy={ isCopying }
						disabled={ isCopying || chosen.length === 0 }
						onClick={ () => setConfirming( true ) }
					>
						{ __( 'Copy the settings', 'italy-cookie-choices' ) }
					</Button>
				</div>
			) }
		</fieldset>
	);
}
