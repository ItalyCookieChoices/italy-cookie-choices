/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, Notice } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { errorMessage, savePolicyTranslation } from './api';
import type { PolicyTranslation } from './settings';

interface Message {
	status: 'success' | 'error';
	text: string;
}

/**
 * The policy link in each language of WPML or Polylang, with the translation of the policy page proposed.
 * The admin chooses: the policy can change between countries. The link is saved in WPML or Polylang.
 * @param props      The props.
 * @param props.rows The languages, printed by the page.
 */
export function PolicyTranslations( props: { rows: PolicyTranslation[] } ) {
	const [ rows, setRows ] = useState( props.rows );
	const [ busy, setBusy ] = useState( '' );
	const [ message, setMessage ] = useState< Message | null >( null );

	if ( rows.length === 0 ) {
		return null;
	}

	async function use( row: PolicyTranslation ): Promise< void > {
		setBusy( row.language );
		setMessage( null );
		try {
			setRows( await savePolicyTranslation( row.language ) );
			setMessage( {
				status: 'success',
				text: sprintf(
					/* translators: %s: the name of the language. */
					__(
						'The policy link of %s is saved.',
						'italy-cookie-choices'
					),
					row.name
				),
			} );
		} catch ( error ) {
			setMessage( {
				status: 'error',
				text: errorMessage(
					error,
					__(
						'The policy link was not saved.',
						'italy-cookie-choices'
					)
				),
			} );
		} finally {
			setBusy( '' );
		}
	}

	return (
		<fieldset className="icc-admin__group">
			<legend>
				{ __( 'Policy link in each language', 'italy-cookie-choices' ) }
			</legend>
			<p>
				{ __(
					'The translation of the policy page in each language, from the multilingual plugin. The policy can change between countries: use a link only when it fits.',
					'italy-cookie-choices'
				) }
			</p>
			{ message !== null && (
				<Notice status={ message.status } isDismissible={ false }>
					{ message.text }
				</Notice>
			) }
			{ rows.map( ( row ) => (
				<div
					className="icc-admin__policy-language"
					key={ row.language }
				>
					<strong>{ row.name }</strong>
					<span>
						{ row.current === ''
							? __(
									'Not translated: the banner links the page of the main language.',
									'italy-cookie-choices'
								)
							: row.current }
					</span>
					{ row.proposal !== '' && row.proposal !== row.current && (
						<div className="icc-admin__suggestion">
							<p>
								{ __(
									'Translation of the policy page:',
									'italy-cookie-choices'
								) }{ ' ' }
								<a
									href={ row.proposal }
									target="_blank"
									rel="noreferrer"
								>
									{ row.proposal }
								</a>
							</p>
							<Button
								variant="secondary"
								isBusy={ busy === row.language }
								disabled={ busy !== '' }
								aria-label={ sprintf(
									/* translators: %s: the name of the language. */
									__(
										'Use this link for %s',
										'italy-cookie-choices'
									),
									row.name
								) }
								onClick={ () => void use( row ) }
							>
								{ __(
									'Use this link',
									'italy-cookie-choices'
								) }
							</Button>
						</div>
					) }
				</div>
			) ) }
		</fieldset>
	);
}
