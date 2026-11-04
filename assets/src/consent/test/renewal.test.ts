import { renewConsentCookie } from '../renewal';
import { CONSENT_CHANGED, type ConsentChange } from '../types';

const URL =
	'https://example.com/wp-json/italy-cookie-choices/v1/consent-cookie';

const consent = {
	id: '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
	categories: [ 'necessary', 'statistics' ],
	version: '1',
	date: '2026-11-04T10:00:00.000Z',
};

const CHOICE: ConsentChange = {
	consent,
	previous: null,
	fromAnotherTab: false,
};
const WITHDRAWAL: ConsentChange = {
	consent: null,
	previous: consent,
	fromAnotherTab: false,
};

/** A request that answers when the test says so. */
function pendingRequest(): { promise: Promise< void >; answer: () => void } {
	let answer = (): void => undefined;
	const promise = new Promise< void >( ( resolve ) => {
		answer = resolve;
	} );

	return { promise, answer };
}

async function settle(): Promise< void > {
	await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
}

describe( 'renewConsentCookie', () => {
	let events: EventTarget;
	let renew: jest.Mock;
	let forget: jest.Mock;

	beforeEach( () => {
		events = new EventTarget();
		renew = jest.fn( () => Promise.resolve() );
		forget = jest.fn();
		renewConsentCookie( events, URL, renew, forget );
	} );

	function dispatch( detail: ConsentChange ): void {
		events.dispatchEvent( new CustomEvent( CONSENT_CHANGED, { detail } ) );
	}

	it( 'asks the server to renew the cookie after a choice', async () => {
		dispatch( CHOICE );
		await settle();

		expect( renew ).toHaveBeenCalledTimes( 1 );
		expect( renew ).toHaveBeenCalledWith( URL );
		expect( forget ).not.toHaveBeenCalled();
	} );

	it( 'does nothing when the consent is withdrawn', async () => {
		dispatch( WITHDRAWAL );
		await settle();

		expect( renew ).not.toHaveBeenCalled();
		expect( forget ).not.toHaveBeenCalled();
	} );

	it( 'leaves a choice made in another tab to that tab', async () => {
		dispatch( { ...CHOICE, fromAnotherTab: true } );
		await settle();

		expect( renew ).not.toHaveBeenCalled();
	} );

	it( 'listens only to its event target', async () => {
		document.dispatchEvent(
			new CustomEvent( CONSENT_CHANGED, { detail: CHOICE } )
		);
		await settle();

		expect( renew ).not.toHaveBeenCalled();
	} );

	it( 'sends the renewals one at a time, in the order of the choices', async () => {
		const first = pendingRequest();
		renew.mockReturnValueOnce( first.promise );

		dispatch( CHOICE );
		dispatch( CHOICE );
		await settle();

		expect( renew ).toHaveBeenCalledTimes( 1 );

		first.answer();
		await settle();

		expect( renew ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'forgets the cookie set again by a renewal answered after a withdrawal', async () => {
		const request = pendingRequest();
		renew.mockReturnValueOnce( request.promise );

		dispatch( CHOICE );
		await settle();
		dispatch( WITHDRAWAL );
		request.answer();
		await settle();

		expect( forget ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'forgets the cookie after a withdrawal made in another tab', async () => {
		const request = pendingRequest();
		renew.mockReturnValueOnce( request.promise );

		dispatch( CHOICE );
		await settle();
		dispatch( { ...WITHDRAWAL, fromAnotherTab: true } );
		request.answer();
		await settle();

		expect( forget ).toHaveBeenCalledTimes( 1 );
	} );
} );
