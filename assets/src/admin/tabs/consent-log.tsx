/* @jsxRuntime classic */
/* @jsx createElement */
import { Button } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { SelectField, ToggleField, type SettingsProps } from '../fields';
import { ConsentStatistics } from '../statistics';

/**
 * The log that demonstrates the consents, GDPR art. 7(1).
 * @param props The settings props and the URL of the CSV export.
 */
export function ConsentLogTab( props: SettingsProps & { exportUrl: string } ) {
	return (
		<Fragment>
			<ToggleField
				{ ...props }
				name="consent_log"
				label={ __( 'Log the consents', 'italy-cookie-choices' ) }
				help={ __(
					'Each choice is stored with a random id, the categories, the policy version and the date. No IP address and no personal data.',
					'italy-cookie-choices'
				) }
			/>
			<SelectField
				{ ...props }
				name="consent_log_retention"
				label={ __( 'Keep the records for', 'italy-cookie-choices' ) }
				defaultValue="12"
				options={ [ '3', '6', '12', '24', '36' ].map( ( months ) => ( {
					value: months,
					label: sprintf(
						/* translators: %s: a number of months. */
						__( '%s months', 'italy-cookie-choices' ),
						months
					),
				} ) ) }
			/>
			<ConsentLogRecords exportUrl={ props.exportUrl } />
		</Fragment>
	);
}

/**
 * The export and the statistics of the records of the site, also on a site that uses the main site.
 * @param props           The props.
 * @param props.exportUrl The URL of the CSV export.
 */
export function ConsentLogRecords( { exportUrl }: { exportUrl: string } ) {
	return (
		<Fragment>
			{ exportUrl !== '' && (
				<Button variant="secondary" href={ exportUrl }>
					{ __( 'Export the log as CSV', 'italy-cookie-choices' ) }
				</Button>
			) }
			<h3>{ __( 'Statistics', 'italy-cookie-choices' ) }</h3>
			<p>
				{ __(
					'The choices of each month, from the records of the log: nothing else is collected. A visitor can choose more than once.',
					'italy-cookie-choices'
				) }
			</p>
			<ConsentStatistics />
		</Fragment>
	);
}
