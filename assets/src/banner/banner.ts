import type { ConsentApi, ConsentChange } from '../consent/types';
import { focusableIn, trapTab } from './focus';

export const OPEN_PREFERENCES = 'italy-cookie-choices:open-preferences';
const HTML_OPEN_CLASS = 'icc-banner-is-open';

export interface BannerElements {
	banner: HTMLElement;
	preferences: HTMLElement;
}

/**
 * The consent banner and the preferences dialog printed by ItalyCookieChoices\Consent\UI\Banner.
 *
 * Accept all, reject all and preferences have the same weight, closing the banner keeps only the necessary cookies.
 */
export class Banner {
	private preferencesTrigger: HTMLElement | null = null;

	public constructor(
		private readonly elements: BannerElements,
		private readonly api: ConsentApi,
		private readonly reload: () => void
	) {}

	public start(): void {
		const { banner, preferences } = this.elements;
		banner.addEventListener( 'click', ( event ) => this.onClick( event ) );
		preferences.addEventListener( 'click', ( event ) =>
			this.onClick( event )
		);
		preferences.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			this.api.setConsent( this.checkedCategories() );
		} );
		banner.addEventListener( 'keydown', ( event ) =>
			this.onBannerKey( event )
		);
		preferences.addEventListener( 'keydown', ( event ) =>
			this.onPreferencesKey( event )
		);

		const document = banner.ownerDocument;
		document.addEventListener( OPEN_PREFERENCES, () =>
			this.openPreferences( null )
		);
		document.addEventListener( 'click', ( event ) =>
			this.onOpenPreferencesLink( event )
		);
		this.api.onChange( ( change ) => this.onConsentChange( change ) );

		if (
			this.api.getConsent() === null &&
			banner.dataset.opensByItself !== '0'
		) {
			this.openBanner();
		}
	}

	public openBanner(): void {
		const { banner } = this.elements;
		banner.hidden = false;
		this.updateHtmlMargin();
		banner.focus();
	}

	public openPreferences( trigger: HTMLElement | null ): void {
		const { banner, preferences } = this.elements;
		this.preferencesTrigger = trigger;
		this.fillCategories();
		preferences.hidden = false;
		banner
			.querySelector( '[data-icc-action="preferences"]' )
			?.setAttribute( 'aria-expanded', 'true' );

		const first = focusableIn( preferences )[ 0 ];
		( first ?? preferences ).focus();
	}

	private closePreferences(): void {
		const { banner, preferences } = this.elements;
		preferences.hidden = true;
		banner
			.querySelector( '[data-icc-action="preferences"]' )
			?.setAttribute( 'aria-expanded', 'false' );
		this.preferencesTrigger?.focus();
		this.preferencesTrigger = null;
	}

	private close(): void {
		const { banner, preferences } = this.elements;
		banner.hidden = true;
		preferences.hidden = true;
		this.updateHtmlMargin();
	}

	private onClick( event: Event ): void {
		const target = event.target as Element | null;
		const button = target?.closest< HTMLElement >( '[data-icc-action]' );
		if ( button === null || button === undefined ) {
			return;
		}

		const action = button.dataset.iccAction;
		if ( action === 'accept-all' ) {
			this.api.acceptAll();
		}

		if ( action === 'reject-all' || action === 'close' ) {
			this.api.rejectAll();
		}

		if ( action === 'preferences' ) {
			this.openPreferences( button );
		}

		if ( action === 'close-preferences' ) {
			this.closePreferences();
		}
	}

	private onOpenPreferencesLink( event: Event ): void {
		const target = event.target as Element | null;
		const link = target?.closest< HTMLElement >(
			'[data-icc-open-preferences], a[href="#icc-preferences"]'
		);
		if ( link === null || link === undefined ) {
			return;
		}

		event.preventDefault();
		this.openPreferences( link );
	}

	private onBannerKey( event: KeyboardEvent ): void {
		const { banner } = this.elements;
		if ( event.key === 'Escape' ) {
			this.api.rejectAll();
			return;
		}

		if ( banner.getAttribute( 'aria-modal' ) === 'true' ) {
			trapTab( banner, event );
		}
	}

	private onPreferencesKey( event: KeyboardEvent ): void {
		if ( event.key === 'Escape' ) {
			event.stopPropagation();
			this.closePreferences();
			return;
		}

		trapTab( this.elements.preferences, event );
	}

	private onConsentChange( change: ConsentChange ): void {
		if ( change.consent === null ) {
			this.openBanner();
			return;
		}

		this.close();
		if (
			! change.fromAnotherTab &&
			this.elements.banner.dataset.reload === '1'
		) {
			this.reload();
		}
	}

	private checkboxes(): HTMLInputElement[] {
		return Array.from(
			this.elements.preferences.querySelectorAll< HTMLInputElement >(
				'input[name="icc-category"]'
			)
		);
	}

	private checkedCategories(): string[] {
		return this.checkboxes()
			.filter( ( checkbox ) => checkbox.checked )
			.map( ( checkbox ) => checkbox.value );
	}

	private fillCategories(): void {
		const consent = this.api.getConsent();
		this.checkboxes().forEach( ( checkbox ) => {
			if ( checkbox.disabled ) {
				return;
			}

			checkbox.checked =
				consent?.categories.includes( checkbox.value ) ?? false;
		} );
	}

	/**
	 * The top bar can push the page down, so it does not cover the header.
	 */
	private updateHtmlMargin(): void {
		const { banner } = this.elements;
		const html = banner.ownerDocument.documentElement;
		if ( banner.dataset.htmlMargin !== '1' || banner.hidden ) {
			html.classList.remove( HTML_OPEN_CLASS );
			html.style.removeProperty( '--icc-banner-height' );
			return;
		}

		html.classList.add( HTML_OPEN_CLASS );
		html.style.setProperty(
			'--icc-banner-height',
			`${ banner.offsetHeight }px`
		);
	}
}
