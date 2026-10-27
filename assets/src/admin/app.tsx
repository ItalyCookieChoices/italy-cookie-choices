/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, Notice, TabPanel } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { errorMessage, loadSettings, saveSettings } from './api';
import { Preview } from './preview';
import type { AdminData, Settings } from './settings';
import { BannerTab } from './tabs/banner';
import { BlockingTab } from './tabs/blocking';
import { CategoriesTab } from './tabs/categories';
import { ConsentLogTab } from './tabs/consent-log';
import { IntegrationsTab } from './tabs/integrations';
import { StyleTab } from './tabs/style';
import { ToolsTab } from './tabs/tools';

interface Message {
	status: 'success' | 'error';
	text: string;
}

function hasChanges( settings: Settings, saved: Settings ): boolean {
	return JSON.stringify( settings ) !== JSON.stringify( saved );
}

/**
 * Asks to confirm before leaving the page with changes not saved.
 * @param isDirty Whether there are changes not saved.
 */
function useLeaveConfirmation( isDirty: boolean ): void {
	useEffect( () => {
		if ( ! isDirty ) {
			return undefined;
		}

		const confirm = ( event: BeforeUnloadEvent ) => event.preventDefault();
		window.addEventListener( 'beforeunload', confirm );

		return () => window.removeEventListener( 'beforeunload', confirm );
	}, [ isDirty ] );
}

/**
 * The settings page: the tabs edit the settings, the preview shows them, the button saves them.
 * @param props      The app props.
 * @param props.data The data printed by the page.
 */
export function App( { data }: { data: AdminData } ) {
	const [ settings, setSettings ] = useState< Settings | null >( null );
	const [ saved, setSaved ] = useState< Settings >( {} );
	const [ message, setMessage ] = useState< Message | null >( null );
	const [ isSaving, setSaving ] = useState( false );
	const isDirty = settings !== null && hasChanges( settings, saved );

	useLeaveConfirmation( isDirty );

	useEffect( () => {
		loadSettings()
			.then( ( loaded ) => {
				setSettings( loaded );
				setSaved( loaded );
			} )
			.catch( ( error: unknown ) =>
				setMessage( {
					status: 'error',
					text: errorMessage(
						error,
						__(
							'The settings were not loaded.',
							'italy-cookie-choices'
						)
					),
				} )
			);
	}, [] );

	function replaceSaved( replaced: Settings, text: string ): void {
		setSettings( replaced );
		setSaved( replaced );
		setMessage( { status: 'success', text } );
	}

	async function save( changed: Settings ): Promise< void > {
		setSaving( true );
		setMessage( null );
		try {
			replaceSaved(
				await saveSettings( changed ),
				__( 'Settings saved.', 'italy-cookie-choices' )
			);
		} catch ( error ) {
			setMessage( {
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

	const notice = message && (
		<Notice status={ message.status } onRemove={ () => setMessage( null ) }>
			{ message.text }
		</Notice>
	);

	if ( settings === null ) {
		return (
			<div className="icc-admin" aria-busy={ notice === null }>
				{ notice ?? (
					<p>
						{ __(
							'Loading the settings…',
							'italy-cookie-choices'
						) }
					</p>
				) }
			</div>
		);
	}

	const props = { settings, onChange: setSettings };
	const tabs = {
		banner: <BannerTab { ...props } />,
		style: <StyleTab { ...props } />,
		categories: (
			<CategoriesTab { ...props } categories={ data.categories } />
		),
		blocking: (
			<BlockingTab
				{ ...props }
				categories={ data.categories }
				vendors={ data.vendors }
			/>
		),
		integrations: <IntegrationsTab { ...props } />,
		'consent-log': (
			<ConsentLogTab
				{ ...props }
				exportUrl={ data.consentLogExportUrl }
			/>
		),
		tools: (
			<ToolsTab
				{ ...props }
				saved={ saved }
				onImported={ ( imported ) =>
					replaceSaved(
						imported,
						__( 'Settings imported.', 'italy-cookie-choices' )
					)
				}
				onError={ ( text ) => setMessage( { status: 'error', text } ) }
			/>
		),
	};

	return (
		<div className="icc-admin">
			{ notice }
			<div className="icc-admin__layout">
				<TabPanel
					className="icc-admin__tabs"
					tabs={ [
						{
							name: 'banner',
							title: __( 'Banner', 'italy-cookie-choices' ),
						},
						{
							name: 'style',
							title: __( 'Style', 'italy-cookie-choices' ),
						},
						{
							name: 'categories',
							title: __( 'Categories', 'italy-cookie-choices' ),
						},
						{
							name: 'blocking',
							title: __( 'Blocking', 'italy-cookie-choices' ),
						},
						{
							name: 'integrations',
							title: __( 'Integrations', 'italy-cookie-choices' ),
						},
						{
							name: 'consent-log',
							title: __( 'Consent log', 'italy-cookie-choices' ),
						},
						{
							name: 'tools',
							title: __( 'Tools', 'italy-cookie-choices' ),
						},
					] }
				>
					{ ( tab ) => (
						<div className="icc-admin__panel">
							{ tabs[ tab.name as keyof typeof tabs ] }
						</div>
					) }
				</TabPanel>
				<Preview settings={ settings } />
			</div>
			<div className="icc-admin__actions">
				<Button
					variant="primary"
					isBusy={ isSaving }
					disabled={ isSaving || ! isDirty }
					onClick={ () => void save( settings ) }
				>
					{ __( 'Save changes', 'italy-cookie-choices' ) }
				</Button>
				{ data.documentationUrl !== '' && (
					<Button
						variant="link"
						href={ data.documentationUrl }
						target="_blank"
					>
						{ __( 'Documentation', 'italy-cookie-choices' ) }
					</Button>
				) }
			</div>
		</div>
	);
}
