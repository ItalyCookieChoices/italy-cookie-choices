/* @jsxRuntime classic */
/* @jsx createElement */
/*
 * Italy Cookie Choices settings page, the data is printed by ItalyCookieChoices\Settings\UI\SettingsPage.
 *
 * The JSX uses the classic runtime in every file: the automatic one needs the react-jsx-runtime script,
 * added in WordPress 6.6.
 */
import { createElement, createRoot } from '@wordpress/element';
import { App } from './app';
import { parseAdminData } from './settings';
import './admin.scss';

declare global {
	interface Window {
		italyCookieChoicesAdmin?: unknown;
	}
}

const root = document.getElementById( 'icc-admin' );
if ( root !== null ) {
	createRoot( root ).render(
		<App data={ parseAdminData( window.italyCookieChoicesAdmin ) } />
	);
}
