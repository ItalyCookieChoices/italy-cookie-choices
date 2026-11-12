/* @jsxRuntime classic */
/* @jsx createElement */
/*
 * Italy Cookie Choices network page, the data is printed by ItalyCookieChoices\Settings\UI\NetworkSettingsPage.
 */
import { createElement, createRoot } from '@wordpress/element';
import { App } from './app';
import { parseNetworkData } from './data';
import './network.scss';

declare global {
	interface Window {
		italyCookieChoicesNetwork?: unknown;
	}
}

const root = document.getElementById( 'icc-network' );
if ( root !== null ) {
	createRoot( root ).render(
		<App data={ parseNetworkData( window.italyCookieChoicesNetwork ) } />
	);
}
