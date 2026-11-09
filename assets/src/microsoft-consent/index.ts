/**
 * Italy Cookie Choices consent signals for Microsoft Advertising (UET) and Microsoft Clarity, printed inline
 * at the start of the head by ItalyCookieChoices\Integrations\UI\MicrosoftConsentScript, before the tags.
 */
import { createConsentApi } from '../consent/api';
import { Cookies } from '../consent/cookies';
import {
	clarityQueue,
	parseMicrosoftConsentConfig,
	startClarityConsent,
	startUetConsent,
	type Clarity,
	type UetQueue,
} from './microsoft-consent';

declare global {
	interface Window {
		italyCookieChoicesMicrosoftConsent?: unknown;
		uetq?: UetQueue;
		clarity?: Clarity;
	}
}

const raw = window.italyCookieChoicesMicrosoftConsent;
if ( typeof raw === 'object' && raw !== null ) {
	const config = parseMicrosoftConsentConfig( raw );
	const consent = createConsentApi(
		config.consent,
		new Cookies( document, window.location.protocol === 'https:' ),
		document
	);

	if ( config.uet !== null ) {
		const uetq: UetQueue = window.uetq ?? ( [] as unknown[] );
		window.uetq = uetq;
		startUetConsent( config.uet, consent, uetq );
	}

	if ( config.clarity !== null ) {
		const clarity = window.clarity ?? clarityQueue();
		window.clarity = clarity;
		startClarityConsent( config.clarity, consent, clarity );
	}
}
