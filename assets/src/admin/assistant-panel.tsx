/* @jsxRuntime classic */
/* @jsx createElement */
import { Button, Notice } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { errorMessage, suggestForScan } from './api';
import type { ScanSuggestions } from './assistant';
import type { Category } from './settings';

interface AskAssistantProps {
	hosts?: readonly string[];
	cookies?: readonly string[];
	onSuggestions: ( suggestions: ScanSuggestions ) => void;
}

/**
 * Asks the AI provider of the site for suggestions, only when the admin clicks: never by itself.
 * @param props               The props.
 * @param props.hosts         The unknown third parties.
 * @param props.cookies       The names of the unknown cookies.
 * @param props.onSuggestions Gets the suggestions.
 */
export function AskAssistant( {
	hosts = [],
	cookies = [],
	onSuggestions,
}: AskAssistantProps ) {
	const [ isAsking, setAsking ] = useState( false );
	const [ error, setError ] = useState( '' );

	async function ask(): Promise< void > {
		setAsking( true );
		setError( '' );
		try {
			onSuggestions( await suggestForScan( hosts, cookies ) );
		} catch ( failure ) {
			setError(
				errorMessage(
					failure,
					__(
						'The AI provider did not answer.',
						'italy-cookie-choices'
					)
				)
			);
		} finally {
			setAsking( false );
		}
	}

	return (
		<div>
			<Button
				variant="secondary"
				isBusy={ isAsking }
				disabled={
					isAsking || ( hosts.length === 0 && cookies.length === 0 )
				}
				onClick={ () => void ask() }
			>
				{ __( 'Suggest with AI', 'italy-cookie-choices' ) }
			</Button>
			<p className="components-form-token-field__help">
				{ __(
					'Sends only these names to the AI provider of the site, no data of the visitors. Check each suggestion before you add it.',
					'italy-cookie-choices'
				) }
			</p>
			{ error !== '' && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
		</div>
	);
}

interface SuggestionNoteProps {
	/** Who the suggestion says sets the cookie or the script. */
	who: string;
	category: string;
	purpose: string;
	categories: readonly Category[];
	onDiscard: () => void;
}

/**
 * A suggestion of the AI provider, the add button of its row uses it, Discard drops it.
 * @param props            The props.
 * @param props.who        Who sets the cookie or the script.
 * @param props.category   The suggested category.
 * @param props.purpose    The suggested purpose.
 * @param props.categories The categories, for their labels.
 * @param props.onDiscard  Drops the suggestion.
 */
export function SuggestionNote( {
	who,
	category,
	purpose,
	categories,
	onDiscard,
}: SuggestionNoteProps ) {
	const label =
		categories.find( ( item ) => item.id === category )?.label ?? category;

	return (
		<div className="icc-admin__suggestion">
			<p>
				{ sprintf(
					/* translators: 1: who sets it, 2: the consent category, 3: the purpose. */
					__( 'Suggested: %1$s, %2$s. %3$s', 'italy-cookie-choices' ),
					who,
					label,
					purpose
				) }
			</p>
			<Button variant="tertiary" onClick={ onDiscard }>
				{ __( 'Discard the suggestion', 'italy-cookie-choices' ) }
			</Button>
		</div>
	);
}
