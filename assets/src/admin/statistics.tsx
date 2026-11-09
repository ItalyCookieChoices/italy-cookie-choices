/* @jsxRuntime classic */
/* @jsx createElement */
import { Notice } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { errorMessage, loadStatistics, type Statistics } from './api';

/**
 * A count with its share of the choices of the month.
 * @param value   The count.
 * @param choices The choices of the month.
 */
function share( value: number, choices: number ): string {
	const percent = choices === 0 ? 0 : Math.round( ( value / choices ) * 100 );

	return sprintf(
		/* translators: 1: a number of choices, 2: its percentage of the choices of the month. */
		__( '%1$d (%2$d%%)', 'italy-cookie-choices' ),
		value,
		percent
	);
}

/**
 * How the visitors answer the banner, by month, from the records of the consent log.
 */
export function ConsentStatistics() {
	const [ statistics, setStatistics ] = useState< Statistics | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	useEffect( () => {
		loadStatistics()
			.then( setStatistics )
			.catch( ( reason: unknown ) =>
				setError(
					errorMessage(
						reason,
						__(
							'The statistics were not loaded.',
							'italy-cookie-choices'
						)
					)
				)
			);
	}, [] );

	if ( error !== null ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	if ( statistics === null ) {
		return (
			<p aria-busy="true">
				{ __( 'Loading the statistics…', 'italy-cookie-choices' ) }
			</p>
		);
	}

	if ( statistics.months.length === 0 ) {
		return <p>{ __( 'No choice logged yet.', 'italy-cookie-choices' ) }</p>;
	}

	return (
		<table className="widefat striped icc-admin__statistics">
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Month', 'italy-cookie-choices' ) }
					</th>
					<th scope="col">
						{ __( 'Choices', 'italy-cookie-choices' ) }
					</th>
					<th scope="col">
						{ __( 'Accept all', 'italy-cookie-choices' ) }
					</th>
					<th scope="col">
						{ __( 'Reject all', 'italy-cookie-choices' ) }
					</th>
					<th scope="col">
						{ __( 'Partial', 'italy-cookie-choices' ) }
					</th>
					<th scope="col">
						{ __( 'Withdrawals', 'italy-cookie-choices' ) }
					</th>
					{ statistics.categories.map( ( category ) => (
						<th scope="col" key={ category.id }>
							{ category.label }
						</th>
					) ) }
				</tr>
			</thead>
			<tbody>
				{ statistics.months.map( ( month ) => (
					<tr key={ month.month }>
						<th scope="row">{ month.month }</th>
						<td>{ month.choices }</td>
						<td>{ share( month.acceptAll, month.choices ) }</td>
						<td>{ share( month.rejectAll, month.choices ) }</td>
						<td>{ share( month.partial, month.choices ) }</td>
						<td>{ share( month.withdrawals, month.choices ) }</td>
						{ statistics.categories.map( ( category ) => (
							<td key={ category.id }>
								{ share(
									month.categories[ category.id ] ?? 0,
									month.choices
								) }
							</td>
						) ) }
					</tr>
				) ) }
			</tbody>
		</table>
	);
}
