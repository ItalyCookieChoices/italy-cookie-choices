/* @jsxRuntime classic */
/* @jsx createElement */
import {
	Button,
	CheckboxControl,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { createElement, Fragment, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { errorMessage, saveScanSchedule } from './api';
import type { Frequency, ScanSchedule, ScanSelection } from './scan';

interface ScanScheduleProps {
	schedule: ScanSchedule;
	/** The pages chosen in the panel, the scheduled scan reads the same. */
	selection: ScanSelection;
	isEmpty: boolean;
}

interface Message {
	status: 'success' | 'error';
	text: string;
}

function frequencyOptions(): Array< { value: Frequency; label: string } > {
	return [
		{ value: 'off', label: __( 'Never', 'italy-cookie-choices' ) },
		{ value: 'daily', label: __( 'Every day', 'italy-cookie-choices' ) },
		{ value: 'weekly', label: __( 'Every week', 'italy-cookie-choices' ) },
		{
			value: 'monthly',
			label: __( 'Every month', 'italy-cookie-choices' ),
		},
	];
}

function joined( items: readonly string[] ): string {
	return items.length === 0 ? '-' : items.join( ', ' );
}

/**
 * The scan that runs by itself on WP-Cron, on the pages chosen in the panel. The schedule is saved with its
 * own button, apart from the settings; the news of each scan show a notice and, when asked, an email.
 * @param props The props.
 */
export function ScanScheduleFields( props: ScanScheduleProps ) {
	const { selection, isEmpty } = props;
	const [ schedule, setSchedule ] = useState( props.schedule );
	const [ frequency, setFrequency ] = useState< Frequency >(
		props.schedule.frequency
	);
	const [ email, setEmail ] = useState( props.schedule.email );
	const [ isSaving, setSaving ] = useState( false );
	const [ message, setMessage ] = useState< Message | null >( null );

	async function save(): Promise< void > {
		setSaving( true );
		setMessage( null );
		try {
			setSchedule(
				await saveScanSchedule( { ...selection, frequency, email } )
			);
			setMessage( {
				status: 'success',
				text: __( 'The schedule is saved.', 'italy-cookie-choices' ),
			} );
		} catch ( error ) {
			setMessage( {
				status: 'error',
				text: errorMessage(
					error,
					__( 'The schedule was not saved.', 'italy-cookie-choices' )
				),
			} );
		} finally {
			setSaving( false );
		}
	}

	const report = schedule.report;

	return (
		<Fragment>
			<h4>{ __( 'Scheduled scan', 'italy-cookie-choices' ) }</h4>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Scan the chosen pages', 'italy-cookie-choices' ) }
				value={ frequency }
				options={ frequencyOptions() }
				onChange={ ( value ) => setFrequency( value as Frequency ) }
			/>
			<CheckboxControl
				__nextHasNoMarginBottom
				label={ sprintf(
					/* translators: %s: the email address of the admin of the site. */
					__(
						'Email the new findings to %s',
						'italy-cookie-choices'
					),
					schedule.adminEmail
				) }
				checked={ email }
				onChange={ setEmail }
			/>
			<div>
				<Button
					variant="secondary"
					isBusy={ isSaving }
					disabled={ isSaving || ( frequency !== 'off' && isEmpty ) }
					onClick={ () => void save() }
				>
					{ __( 'Save the schedule', 'italy-cookie-choices' ) }
				</Button>
			</div>
			{ message !== null && (
				<Notice status={ message.status } isDismissible={ false }>
					{ message.text }
				</Notice>
			) }
			{ report !== null && (
				<p>
					{ sprintf(
						/* translators: 1: the date, 2: the pages scanned, 3: the vendors not blocked, 4: the unknown third parties. */
						__(
							'Last scheduled scan: %1$s, %2$d pages. Vendors not blocked: %3$s. Unknown third parties: %4$s.',
							'italy-cookie-choices'
						),
						report.date,
						report.pages,
						joined( report.vendors ),
						joined( report.hosts )
					) }
				</p>
			) }
		</Fragment>
	);
}
