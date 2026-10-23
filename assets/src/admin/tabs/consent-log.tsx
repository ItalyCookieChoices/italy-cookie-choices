/* @jsxRuntime classic */
/* @jsx createElement */
import { Button } from '@wordpress/components';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { SelectField, ToggleField, type SettingsProps } from '../fields';

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
			{ props.exportUrl !== '' && (
				<Button variant="secondary" href={ props.exportUrl }>
					{ __( 'Export the log as CSV', 'italy-cookie-choices' ) }
				</Button>
			) }
		</Fragment>
	);
}
