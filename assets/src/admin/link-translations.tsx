/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, Notice } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { errorMessage, saveLinkTranslation } from './api';
import type { BannerLink, LinkTranslation } from './settings';

interface Message {
	status: 'success' | 'error';
	text: string;
}

interface LinkTexts {
	legend: string;
	description: string;
	/** The message after the save in a language. */
	saved: ( language: string ) => string;
	notSaved: string;
	proposal: string;
}

function textsOf( link: BannerLink ): LinkTexts {
	if ( link === 'legal-notice' ) {
		return {
			legend: __(
				'Legal notice link in each language',
				'italy-cookie-choices'
			),
			description: __(
				'The translation of the legal notice page in each language, from the multilingual plugin.',
				'italy-cookie-choices'
			),
			saved: ( language ) =>
				sprintf(
					/* translators: %s: the name of the language. */
					__(
						'The legal notice link of %s is saved.',
						'italy-cookie-choices'
					),
					language
				),
			notSaved: __(
				'The legal notice link was not saved.',
				'italy-cookie-choices'
			),
			proposal: __(
				'Translation of the legal notice page:',
				'italy-cookie-choices'
			),
		};
	}

	return {
		legend: __( 'Policy link in each language', 'italy-cookie-choices' ),
		description: __(
			'The translation of the policy page in each language, from the multilingual plugin. The policy can change between countries: use a link only when it fits.',
			'italy-cookie-choices'
		),
		saved: ( language ) =>
			sprintf(
				/* translators: %s: the name of the language. */
				__( 'The policy link of %s is saved.', 'italy-cookie-choices' ),
				language
			),
		notSaved: __(
			'The policy link was not saved.',
			'italy-cookie-choices'
		),
		proposal: __(
			'Translation of the policy page:',
			'italy-cookie-choices'
		),
	};
}

/**
 * A link of the banner in each language of WPML or Polylang, with the translation of its page proposed.
 * The admin chooses: the policy can change between countries. The link is saved in WPML or Polylang.
 * @param props      The props.
 * @param props.link The link of the banner.
 * @param props.rows The languages, printed by the page.
 */
export function LinkTranslations( props: {
	link: BannerLink;
	rows: LinkTranslation[];
} ) {
	const [ rows, setRows ] = useState( props.rows );
	const [ busy, setBusy ] = useState( '' );
	const [ message, setMessage ] = useState< Message | null >( null );

	if ( rows.length === 0 ) {
		return null;
	}

	const texts = textsOf( props.link );

	async function use( row: LinkTranslation ): Promise< void > {
		setBusy( row.language );
		setMessage( null );
		try {
			setRows( await saveLinkTranslation( props.link, row.language ) );
			setMessage( {
				status: 'success',
				text: texts.saved( row.name ),
			} );
		} catch ( error ) {
			setMessage( {
				status: 'error',
				text: errorMessage( error, texts.notSaved ),
			} );
		} finally {
			setBusy( '' );
		}
	}

	return (
		<fieldset className="icc-admin__group">
			<legend>{ texts.legend }</legend>
			<p>{ texts.description }</p>
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
								{ texts.proposal }{ ' ' }
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
