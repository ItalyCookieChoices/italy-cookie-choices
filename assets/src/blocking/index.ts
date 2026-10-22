/**
 * Italy Cookie Choices blocking, it needs the consent API of assets/src/consent.
 *
 * Block a script or a frame by hand with its category:
 * <script type="text/plain" data-category="statistics" src="https://example.com/analytics.js"></script>
 * <iframe data-category="marketing" data-icc-src="https://example.com/embed"></iframe>
 */
import './blocking.scss';
import { Blocker } from './blocker';

function start(): void {
	const api = window.italyCookieChoices;
	if ( api === undefined ) {
		return;
	}

	void new Blocker( document, api ).start();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}
