/**
 * Italy Cookie Choices consent banner, it needs the consent API of assets/src/consent.
 */
import './banner.scss';
import { Banner } from './banner';

function start(): void {
	const banner = document.getElementById( 'icc-banner' );
	const preferences = document.getElementById( 'icc-preferences' );
	const api = window.italyCookieChoices;
	if ( banner === null || preferences === null || api === undefined ) {
		return;
	}

	new Banner( { banner, preferences }, api, () =>
		window.location.reload()
	).start();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}
