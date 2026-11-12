/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, ToggleControl } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { errorMessage } from '../admin/api';
import { saveNetwork } from './api';
import type { Message } from './app';

interface SharingPanelProps {
	shared: boolean;
	onSaved: ( shared: boolean ) => void;
	onMessage: ( message: Message ) => void;
}

/**
 * The switch that gives the settings of the main site to every site.
 * @param props The props.
 */
export function SharingPanel( props: SharingPanelProps ) {
	const { shared, onSaved, onMessage } = props;
	const [ value, setValue ] = useState( shared );
	const [ isSaving, setSaving ] = useState( false );

	async function save(): Promise< void > {
		setSaving( true );
		try {
			onSaved( ( await saveNetwork( value ) ).shared );
			onMessage( {
				status: 'success',
				text: __( 'Settings saved.', 'italy-cookie-choices' ),
			} );
		} catch ( error ) {
			onMessage( {
				status: 'error',
				text: errorMessage(
					error,
					__( 'The settings were not saved.', 'italy-cookie-choices' )
				),
			} );
		} finally {
			setSaving( false );
		}
	}

	return (
		<fieldset className="icc-network__group">
			<legend>
				{ __( 'Settings of the sites', 'italy-cookie-choices' ) }
			</legend>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Use the settings of the main site on every site',
					'italy-cookie-choices'
				) }
				help={ __(
					'Each site keeps its own texts, for its language, and its own consent log.',
					'italy-cookie-choices'
				) }
				checked={ value }
				onChange={ setValue }
			/>
			<div>
				<Button
					variant="primary"
					isBusy={ isSaving }
					disabled={ isSaving || value === shared }
					onClick={ () => void save() }
				>
					{ __( 'Save', 'italy-cookie-choices' ) }
				</Button>
			</div>
		</fieldset>
	);
}
