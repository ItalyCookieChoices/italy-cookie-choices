import { createConsentApi } from '../api';
import { Cookies } from '../cookies';
import { syncTabs } from '../tab-sync';
import {
	CONSENT_CHANGED,
	type ConsentChange,
	type ConsentConfig,
} from '../types';

const config: ConsentConfig = {
	cookieName: 'icc_consent',
	categories: [ { id: 'necessary', required: true } ],
	version: '1',
	durationDays: 182,
	legacyCookie: null,
};

class FakeChannel extends EventTarget {
	public readonly posted: unknown[] = [];

	public postMessage( message: unknown ): void {
		this.posted.push( message );
	}

	public receive( data: unknown ): void {
		this.dispatchEvent( new MessageEvent( 'message', { data } ) );
	}
}

function setUp() {
	const events = new EventTarget();
	const channel = new FakeChannel();
	const api = createConsentApi(
		config,
		new Cookies( document, false ),
		events
	);
	syncTabs( api, events, channel as unknown as BroadcastChannel );

	const changes: ConsentChange[] = [];
	events.addEventListener( CONSENT_CHANGED, ( event ) => {
		changes.push( ( event as CustomEvent< ConsentChange > ).detail );
	} );

	return { api, channel, changes };
}

describe( 'syncTabs', () => {
	it( 'tells the other tabs when the consent changes here', () => {
		const { api, channel } = setUp();

		const consent = api.acceptAll();

		expect( channel.posted ).toEqual( [ { consent, previous: null } ] );
	} );

	it( 'dispatches the changes made in another tab without sending them back', () => {
		const { channel, changes } = setUp();
		const consent = {
			categories: [ 'necessary' ],
			version: '1',
			date: '2026-10-22T10:00:00.000Z',
		};

		channel.receive( { consent, previous: null } );

		expect( changes ).toEqual( [
			{ consent, previous: null, fromAnotherTab: true },
		] );
		expect( channel.posted ).toEqual( [] );
	} );

	it( 'reads an invalid message as a withdrawn consent', () => {
		const { channel, changes } = setUp();

		channel.receive( null );

		expect( changes ).toEqual( [
			{ consent: null, previous: null, fromAnotherTab: true },
		] );
	} );

	it( 'does nothing without the channel', () => {
		const events = new EventTarget();
		const api = createConsentApi(
			config,
			new Cookies( document, false ),
			events
		);

		expect( () => syncTabs( api, events, null ) ).not.toThrow();
	} );
} );
