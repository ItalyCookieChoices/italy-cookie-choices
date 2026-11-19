/* @jsxRuntime classic */
/* @jsx createElement */
import { Button } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	errorMessage,
	importMigration,
	previewMigration,
	type MigrationResult,
} from './api';
import type { MigrationSource, Settings } from './settings';

interface MigrationPanelProps {
	sources: MigrationSource[];
	onImported: ( settings: Settings ) => void;
	onError: ( message: string ) => void;
}

interface Shown extends MigrationResult {
	source: string;
	isImported: boolean;
}

function ResultList( props: { title: string; items: string[] } ) {
	if ( props.items.length === 0 ) {
		return null;
	}

	return (
		<div>
			<p>
				<strong>{ props.title }</strong>
			</p>
			<ul className="icc-admin__list">
				{ props.items.map( ( item ) => (
					<li key={ item }>{ item }</li>
				) ) }
			</ul>
		</div>
	);
}

/**
 * Imports the settings of the other consent plugins found in the database, after a preview.
 * @param props            The props.
 * @param props.sources    The plugins, printed by the page.
 * @param props.onImported Receives the saved settings.
 * @param props.onError    Receives the message of a failure.
 */
export function MigrationPanel( {
	sources,
	onImported,
	onError,
}: MigrationPanelProps ) {
	const [ busy, setBusy ] = useState( '' );
	const [ shown, setShown ] = useState< Shown | null >( null );

	if ( sources.length === 0 ) {
		return null;
	}

	async function run(
		source: MigrationSource,
		isImport: boolean
	): Promise< void > {
		setBusy( source.id );
		try {
			if ( ! isImport ) {
				const result = await previewMigration( source.id );
				setShown( { ...result, source: source.id, isImported: false } );
				return;
			}

			const { settings, ...result } = await importMigration( source.id );
			setShown( { ...result, source: source.id, isImported: true } );
			onImported( settings );
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
			setBusy( '' );
		}
	}

	return (
		<fieldset className="icc-admin__group">
			<legend>
				{ __( 'Import from another plugin', 'italy-cookie-choices' ) }
			</legend>
			<p>
				{ __(
					'The settings of these plugins are in the database. The import replaces the settings it brings, the others stay; the visitors are asked for the consent again.',
					'italy-cookie-choices'
				) }
			</p>
			{ sources.map( ( source ) => (
				<div className="icc-admin__migration" key={ source.id }>
					<strong>{ source.name }</strong>
					<Button
						variant="secondary"
						disabled={ busy !== '' }
						isBusy={ busy === source.id }
						aria-label={ sprintf(
							/* translators: %s: the name of the plugin. */
							__(
								'Preview the import of %s',
								'italy-cookie-choices'
							),
							source.name
						) }
						onClick={ () => void run( source, false ) }
					>
						{ __( 'Preview', 'italy-cookie-choices' ) }
					</Button>
					<Button
						variant="primary"
						disabled={ busy !== '' }
						aria-label={ sprintf(
							/* translators: %s: the name of the plugin. */
							__(
								'Import the settings of %s',
								'italy-cookie-choices'
							),
							source.name
						) }
						onClick={ () => void run( source, true ) }
					>
						{ __( 'Import', 'italy-cookie-choices' ) }
					</Button>
				</div>
			) ) }
			{ shown !== null && (
				<div aria-live="polite">
					<ResultList
						title={
							shown.isImported
								? __( 'Imported:', 'italy-cookie-choices' )
								: __(
										'The import changes:',
										'italy-cookie-choices'
									)
						}
						items={ shown.summary }
					/>
					<ResultList
						title={ __( 'To check:', 'italy-cookie-choices' ) }
						items={ shown.notes }
					/>
				</div>
			) }
		</fieldset>
	);
}
