import {
	CONSENT_CHANGED,
	type Consent,
	type ConsentChange,
} from '../../consent/types';
import { entryOf, logConsentChanges, sendWithFetch } from '../sender';

const URL = 'https://example.com/wp-json/italy-cookie-choices/v1/consents';

const consent: Consent = {
	id: '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
	categories: [ 'necessary', 'statistics' ],
	version: '1',
	date: '2026-10-22T10:00:00.000Z',
};

function change( detail: ConsentChange ): CustomEvent< ConsentChange > {
	return new CustomEvent< ConsentChange >( CONSENT_CHANGED, { detail } );
}

describe( 'entryOf', () => {
	it( 'sends the choice', () => {
		expect(
			entryOf( { consent, previous: null, fromAnotherTab: false } )
		).toEqual( {
			id: consent.id,
			categories: [ 'necessary', 'statistics' ],
			version: '1',
		} );
	} );

	it( 'sends a withdrawal without categories', () => {
		expect(
			entryOf( {
				consent: null,
				previous: consent,
				fromAnotherTab: false,
			} )
		).toEqual( { id: consent.id, categories: [], version: '1' } );
	} );

	it( 'does not send the changes made in another tab', () => {
		expect(
			entryOf( { consent, previous: null, fromAnotherTab: true } )
		).toBeNull();
	} );

	it( 'does not send a withdrawal without a consent', () => {
		expect(
			entryOf( { consent: null, previous: null, fromAnotherTab: false } )
		).toBeNull();
	} );
} );

describe( 'logConsentChanges', () => {
	it( 'sends each change to the log', () => {
		const events = new EventTarget();
		const send = jest.fn();
		logConsentChanges( events, URL, send );

		events.dispatchEvent(
			change( { consent, previous: null, fromAnotherTab: false } )
		);
		events.dispatchEvent(
			change( { consent, previous: null, fromAnotherTab: true } )
		);

		expect( send ).toHaveBeenCalledTimes( 1 );
		expect( send ).toHaveBeenCalledWith( URL, {
			id: consent.id,
			categories: [ 'necessary', 'statistics' ],
			version: '1',
		} );
	} );
} );

describe( 'sendWithFetch', () => {
	it( 'posts the entry without cookies and survives the reload', () => {
		const fetchMock = jest.fn( () => Promise.resolve( {} ) );
		window.fetch = fetchMock as unknown as typeof fetch;

		sendWithFetch( URL, { id: consent.id, categories: [], version: '1' } );

		expect( fetchMock ).toHaveBeenCalledWith( URL, {
			method: 'POST',
			keepalive: true,
			credentials: 'omit',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( {
				id: consent.id,
				categories: [],
				version: '1',
			} ),
		} );
	} );
} );
