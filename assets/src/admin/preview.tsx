/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, ButtonGroup, Notice } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { previewHtml, type Dialog } from './api';
import type { Settings } from './settings';

const PREVIEW_DELAY = 400;

/**
 * The banner of the settings not saved yet, rendered by the server with the front end template and styles.
 * The frame is sandboxed, the page has no scripts.
 * @param props          The preview props.
 * @param props.settings The settings to preview.
 */
export function Preview( { settings }: { settings: Settings } ) {
	const [ dialog, setDialog ] = useState< Dialog >( 'banner' );
	const [ html, setHtml ] = useState( '' );
	const [ hasFailed, setFailed ] = useState( false );

	useEffect( () => {
		let isCurrent = true;
		const timer = setTimeout( () => {
			previewHtml( settings, dialog )
				.then( ( preview ) => {
					if ( isCurrent ) {
						setHtml( preview );
						setFailed( false );
					}
				} )
				.catch( () => isCurrent && setFailed( true ) );
		}, PREVIEW_DELAY );

		return () => {
			isCurrent = false;
			clearTimeout( timer );
		};
	}, [ settings, dialog ] );

	return (
		<section
			className="icc-admin__preview"
			aria-labelledby="icc-admin-preview-title"
		>
			<h2 id="icc-admin-preview-title">
				{ __( 'Preview', 'italy-cookie-choices' ) }
			</h2>
			<ButtonGroup
				aria-label={ __( 'Dialog to preview', 'italy-cookie-choices' ) }
			>
				<Button
					variant="secondary"
					isPressed={ dialog === 'banner' }
					onClick={ () => setDialog( 'banner' ) }
				>
					{ __( 'Banner', 'italy-cookie-choices' ) }
				</Button>
				<Button
					variant="secondary"
					isPressed={ dialog === 'preferences' }
					onClick={ () => setDialog( 'preferences' ) }
				>
					{ __( 'Preferences', 'italy-cookie-choices' ) }
				</Button>
			</ButtonGroup>
			{ hasFailed && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'The preview is not available.',
						'italy-cookie-choices'
					) }
				</Notice>
			) }
			<iframe
				className="icc-admin__preview-frame"
				title={ __( 'Banner preview', 'italy-cookie-choices' ) }
				sandbox=""
				srcDoc={ html }
			/>
		</section>
	);
}
