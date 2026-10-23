/* @jsxRuntime classic */
/* @jsx createElement */
import { Button } from '@wordpress/components';
import { createElement, Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { errorMessage, importSettings } from '../api';
import { TextField, type SettingsProps } from '../fields';
import type { Settings } from '../settings';
import { downloadSettings, readJsonFile } from '../transfer';

interface ToolsTabProps extends SettingsProps {
	saved: Settings;
	onImported: ( settings: Settings ) => void;
	onError: ( message: string ) => void;
}

/**
 * The cookie of the 2.x banner, and the export and the import of the settings.
 * @param props The tab props.
 */
export function ToolsTab( props: ToolsTabProps ) {
	const { saved, onImported, onError } = props;
	const [ isImporting, setImporting ] = useState( false );

	async function importFile( file: File | undefined ): Promise< void > {
		if ( file === undefined ) {
			return;
		}

		setImporting( true );
		try {
			const json = await readJsonFile( file );
			if ( json === null ) {
				onError(
					__(
						'The file is not a valid JSON file.',
						'italy-cookie-choices'
					)
				);
				return;
			}

			onImported( await importSettings( json ) );
		} catch ( error ) {
			onError(
				errorMessage(
					error,
					__(
						'The settings were not imported.',
						'italy-cookie-choices'
					)
				)
			);
		} finally {
			setImporting( false );
		}
	}

	return (
		<Fragment>
			<fieldset className="icc-admin__group">
				<legend>
					{ __(
						'Cookie of the 2.x versions',
						'italy-cookie-choices'
					) }
				</legend>
				<p>
					{ __(
						'Written while every category is accepted, for the code that still reads it.',
						'italy-cookie-choices'
					) }
				</p>
				<TextField
					{ ...props }
					name="cookie_name"
					label={ __( 'Name', 'italy-cookie-choices' ) }
				/>
				<TextField
					{ ...props }
					name="cookie_value"
					label={ __( 'Value', 'italy-cookie-choices' ) }
				/>
			</fieldset>
			<fieldset className="icc-admin__group">
				<legend>{ __( 'Export', 'italy-cookie-choices' ) }</legend>
				<p>
					{ __(
						'Downloads the saved settings as a JSON file.',
						'italy-cookie-choices'
					) }
				</p>
				<Button
					variant="secondary"
					onClick={ () => downloadSettings( saved, new Date() ) }
				>
					{ __( 'Export the settings', 'italy-cookie-choices' ) }
				</Button>
			</fieldset>
			<fieldset className="icc-admin__group">
				<legend>{ __( 'Import', 'italy-cookie-choices' ) }</legend>
				<p>
					{ __(
						'Replaces the saved settings with the ones of an exported file, also of the 2.x versions.',
						'italy-cookie-choices'
					) }
				</p>
				<label htmlFor="icc-admin-import">
					{ __( 'Settings file', 'italy-cookie-choices' ) }
				</label>
				<input
					id="icc-admin-import"
					type="file"
					accept=".json,application/json"
					disabled={ isImporting }
					onChange={ ( event ) => {
						const input = event.currentTarget;
						void importFile( input.files?.[ 0 ] ).then( () => {
							input.value = '';
						} );
					} }
				/>
			</fieldset>
		</Fragment>
	);
}
