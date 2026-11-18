import type { ConsentApi, ConsentChange } from '../consent/types';
import { focusableIn, trapTab } from './focus';

export const OPEN_PREFERENCES = 'italy-cookie-choices:open-preferences';
const HTML_OPEN_CLASS = 'icc-banner-is-open';
/** The category left out by accept all when the browser sends the Global Privacy Control signal. */
const MARKETING = 'marketing';

export interface BannerElements {
	banner: HTMLElement;
	preferences: HTMLElement;
	/** The floating button that opens the preferences, when it is turned on. */
	floatingButton?: HTMLElement | null;
}

/**
 * The consent banner and the preferences dialog printed by ItalyCookieChoices\Consent\UI\Banner.
 *
 * Accept all, reject all and preferences have the same weight, closing the banner keeps only the necessary cookies.
 * When the site respects the Global Privacy Control signal and the browser sends it, accept all leaves out
 * the marketing category: the visitor can still choose it in the preferences.
 */
export class Banner {
	private preferencesTrigger: HTMLElement | null = null;

	public constructor(
		private readonly elements: BannerElements,
		private readonly api: ConsentApi,
		private readonly reload: () => void,
		private readonly browserSendsGlobalPrivacyControl: () => boolean = () =>
			false
	) {}

	public start(): void {
		const { banner, preferences } = this.elements;
		banner.addEventListener( 'click', ( event ) => this.onClick( event ) );
		preferences.addEventListener( 'click', ( event ) =>
			this.onClick( event )
		);
		preferences.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			this.api.setConsent(
				this.checkedCategories(),
				this.uncheckedVendors()
			);
		} );
		preferences.addEventListener( 'change', ( event ) =>
			this.onChoiceChange( event )
		);
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
		this.showGlobalPrivacyControlNotes();

		if (
			this.api.getConsent() === null &&
			banner.dataset.opensByItself !== '0'
		) {
			this.openBanner();
		}

		this.updateFloatingButton();
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

	/**
	 * The floating button is there only after a choice, before it the banner asks for the consent.
	 */
	private updateFloatingButton(): void {
		const { floatingButton } = this.elements;
		if ( floatingButton === null || floatingButton === undefined ) {
			return;
		}

		floatingButton.hidden = this.api.getConsent() === null;
	}

	private onClick( event: Event ): void {
		const target = event.target as Element | null;
		const button = target?.closest< HTMLElement >( '[data-icc-action]' );
		if ( button === null || button === undefined ) {
			return;
		}

		const action = button.dataset.iccAction;
		if ( action === 'accept-all' ) {
			this.acceptAll();
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
		this.updateFloatingButton();
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

	private acceptAll(): void {
		if ( ! this.respectsGlobalPrivacyControl() ) {
			this.api.acceptAll();
			return;
		}

		this.api.setConsent(
			this.api
				.categories()
				.map( ( category ) => category.id )
				.filter( ( id ) => id !== MARKETING )
		);
	}

	private respectsGlobalPrivacyControl(): boolean {
		return (
			this.elements.banner.dataset.globalPrivacyControl === '1' &&
			this.browserSendsGlobalPrivacyControl()
		);
	}

	private showGlobalPrivacyControlNotes(): void {
		if ( ! this.respectsGlobalPrivacyControl() ) {
			return;
		}

		const { banner, preferences } = this.elements;
		[ banner, preferences ].forEach( ( dialog ) =>
			dialog
				.querySelectorAll< HTMLElement >( '[data-icc-gpc-note]' )
				.forEach( ( note ) => {
					note.hidden = false;
				} )
		);
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

	/**
	 * The switches of the vendors, with the consent per vendor.
	 * @param category Only the vendors of this category, all of them when it is empty.
	 */
	private vendorCheckboxes( category = '' ): HTMLInputElement[] {
		return Array.from(
			this.elements.preferences.querySelectorAll< HTMLInputElement >(
				'input[name="icc-vendor"]'
			)
		).filter(
			( checkbox ) =>
				category === '' || checkbox.dataset.iccCategory === category
		);
	}

	private uncheckedVendors(): string[] {
		return this.vendorCheckboxes()
			.filter( ( checkbox ) => ! checkbox.checked )
			.map( ( checkbox ) => checkbox.value );
	}

	/**
	 * A category turns its vendors on and off, a vendor turned on turns its category on.
	 * @param event The change of a switch.
	 */
	private onChoiceChange( event: Event ): void {
		const input = event.target;
		if ( ! ( input instanceof HTMLInputElement ) ) {
			return;
		}

		if ( input.name === 'icc-category' ) {
			this.vendorCheckboxes( input.value ).forEach( ( checkbox ) => {
				checkbox.checked = input.checked;
			} );
			return;
		}

		const category = this.checkboxes().find(
			( checkbox ) => checkbox.value === input.dataset.iccCategory
		);
		if ( input.name === 'icc-vendor' && input.checked && category ) {
			category.checked = true;
		}
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
		this.vendorCheckboxes().forEach( ( checkbox ) => {
			checkbox.checked = this.api.hasVendorConsent(
				checkbox.value,
				checkbox.dataset.iccCategory ?? ''
			);
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
