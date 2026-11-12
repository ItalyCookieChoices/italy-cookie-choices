/* @jsxRuntime classic */
/* @jsx createElement */
import { Notice } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { CopyPanel } from './copy';
import type { NetworkData } from './data';
import { SharingPanel } from './sharing';

export interface Message {
	status: 'success' | 'error';
	text: string;
}

/**
 * The network page: the switch of the main site and, while every site has its own settings, the copy.
 * @param props      The props.
 * @param props.data The data printed by the page.
 */
export function App( { data }: { data: NetworkData } ) {
	const [ shared, setShared ] = useState( data.shared );
	const [ message, setMessage ] = useState< Message | null >( null );

	return (
		<div className="icc-network">
			{ message !== null && (
				<Notice
					status={ message.status }
					onRemove={ () => setMessage( null ) }
				>
					{ message.text }
				</Notice>
			) }
			<SharingPanel
				shared={ shared }
				onSaved={ setShared }
				onMessage={ setMessage }
			/>
			<CopyPanel
				shared={ shared }
				mainSiteId={ data.mainSiteId }
				sites={ data.sites }
				onMessage={ setMessage }
			/>
		</div>
	);
}
