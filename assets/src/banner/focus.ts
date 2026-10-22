const FOCUSABLE = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'[tabindex]:not([tabindex="-1"])',
].join( ',' );

export function focusableIn( container: HTMLElement ): HTMLElement[] {
	return Array.from(
		container.querySelectorAll< HTMLElement >( FOCUSABLE )
	).filter( ( element ) => ! element.closest( '[hidden]' ) );
}

/**
 * Keeps the Tab key inside the container, like a modal dialog.
 *
 * @param container The dialog.
 * @param event     The keydown event.
 */
export function trapTab( container: HTMLElement, event: KeyboardEvent ): void {
	if ( event.key !== 'Tab' ) {
		return;
	}

	const focusable = focusableIn( container );
	const first = focusable[ 0 ];
	const last = focusable[ focusable.length - 1 ];
	if ( first === undefined || last === undefined ) {
		event.preventDefault();
		return;
	}

	const active = container.ownerDocument.activeElement;
	if ( event.shiftKey && ( active === first || active === container ) ) {
		event.preventDefault();
		last.focus();
		return;
	}

	if ( ! event.shiftKey && active === last ) {
		event.preventDefault();
		first.focus();
	}
}
