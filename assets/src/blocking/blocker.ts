import type { ConsentApi } from '../consent/types';

/** Printed by ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker, or by hand. */
const BLOCKED_SCRIPTS = 'script[type="text/plain"][data-category]';
const BLOCKED_FRAMES =
	'iframe[data-icc-src][data-category], embed[data-icc-src][data-category]';
/** Printed by ItalyCookieChoices\Blocking\UI\CustomScriptsPrinter: code added to the page after the consent. */
const BLOCKED_CODE = 'template[data-icc-code][data-category]';
const SOURCE = 'data-icc-src';
const TYPE = 'data-icc-type';
const HIDDEN = 'data-icc-hidden';
const VENDOR = 'data-icc-vendor';
const ACCEPT_BUTTON = '[data-icc-accept]';
const PLACEHOLDERS = 'template[data-icc-placeholder]';
const PREFERENCES_LINK = '.icc-placeholder__preferences';
const WEB_PROTOCOLS = [ 'http:', 'https:' ];

/**
 * Whether the address loads a page of the web, relative to the page or not: never a javascript: or data: one.
 * @param address The address of the frame.
 * @param base    The address of the page.
 */
export function isWebAddress( address: string, base: string ): boolean {
	try {
		return WEB_PROTOCOLS.includes(
			new URL( address.trim(), base ).protocol
		);
	} catch {
		return false;
	}
}

/**
 * Runs the blocked scripts and loads the blocked frames once their category has the consent.
 * A blocked code template is added to the page in its place, its blocked scripts run with the others.
 *
 * The scripts run in the page order, each one after the previous script file is loaded.
 * A frame shows the placeholder of its category until the consent, and again if the consent is withdrawn.
 */
export class Blocker {
	private readonly placeholders = new Map< Element, Element >();
	private scripts: Promise< void > = Promise.resolve();

	public constructor(
		private readonly document: Document,
		private readonly api: ConsentApi
	) {}

	public start(): Promise< void > {
		this.api.onChange( () => {
			this.update();
		} );

		return this.update();
	}

	/**
	 * @return Resolved when the scripts with the consent have run.
	 */
	public update(): Promise< void > {
		this.document
			.querySelectorAll( BLOCKED_FRAMES )
			.forEach( ( frame ) => this.updateFrame( frame ) );
		this.scripts = this.scripts.then( () => {
			this.addCode();

			return this.runScripts();
		} );

		return this.scripts;
	}

	/**
	 * The consent to the category of an element and, with the consent per vendor, to its vendor.
	 * @param element A blocked script or frame.
	 */
	private allows( element: Element ): boolean {
		const category = element.getAttribute( 'data-category' ) ?? '';
		const vendor = element.getAttribute( VENDOR ) ?? '';

		return vendor === ''
			? this.api.hasConsent( category )
			: this.api.hasVendorConsent( vendor, category );
	}

	private updateFrame( frame: Element ): void {
		if ( ! this.allows( frame ) ) {
			frame.removeAttribute( 'src' );
			this.showPlaceholder( frame );
			return;
		}

		const source = frame.getAttribute( SOURCE ) ?? '';
		if ( ! isWebAddress( source, this.document.baseURI ) ) {
			return;
		}

		this.placeholders.get( frame )?.remove();
		this.placeholders.delete( frame );
		frame.removeAttribute( HIDDEN );
		if ( frame.getAttribute( 'src' ) !== source ) {
			frame.setAttribute( 'src', source );
		}
	}

	private showPlaceholder( frame: Element ): void {
		if ( this.placeholders.has( frame ) ) {
			return;
		}

		const placeholder = this.placeholderOf(
			frame.getAttribute( 'data-category' ) ?? ''
		);
		if ( placeholder === null ) {
			return;
		}

		// The button accepts the vendor of the frame, with the consent per vendor.
		const vendor = frame.getAttribute( VENDOR ) ?? '';
		if ( vendor !== '' ) {
			placeholder
				.querySelector( ACCEPT_BUTTON )
				?.setAttribute( VENDOR, vendor );
		}

		const width = frame.getAttribute( 'width' ) ?? '';
		const height = frame.getAttribute( 'height' ) ?? '';
		if ( /^\d+$/.test( width ) && /^\d+$/.test( height ) ) {
			placeholder.style.aspectRatio = `${ width } / ${ height }`;
			placeholder.style.maxWidth = `${ width }px`;
		}

		frame.before( placeholder );
		frame.setAttribute( HIDDEN, '' );
		this.placeholders.set( frame, placeholder );
	}

	private placeholderOf( category: string ): HTMLElement | null {
		const template = Array.from(
			this.document.querySelectorAll< HTMLTemplateElement >(
				PLACEHOLDERS
			)
		).find(
			( candidate ) => candidate.dataset.iccPlaceholder === category
		);
		const placeholder =
			template?.content.firstElementChild?.cloneNode( true );
		if ( ! ( placeholder instanceof HTMLElement ) ) {
			return null;
		}

		// The preferences dialog is not printed where the banner is hidden, like the policy page.
		if ( this.document.getElementById( 'icc-preferences' ) === null ) {
			placeholder.querySelector( PREFERENCES_LINK )?.remove();
		}

		return placeholder;
	}

	private addCode(): void {
		this.document
			.querySelectorAll< HTMLTemplateElement >( BLOCKED_CODE )
			.forEach( ( template ) => {
				if ( this.allows( template ) ) {
					template.replaceWith(
						this.document.importNode( template.content, true )
					);
				}
			} );
	}

	private async runScripts(): Promise< void > {
		const scripts = Array.from(
			this.document.querySelectorAll< HTMLScriptElement >(
				BLOCKED_SCRIPTS
			)
		).filter( ( script ) => this.allows( script ) );

		for ( const script of scripts ) {
			await this.run( script );
		}
	}

	private run( blocked: HTMLScriptElement ): Promise< void > {
		const script = this.document.createElement( 'script' );
		Array.from( blocked.attributes ).forEach( ( { name, value } ) => {
			if ( name !== 'type' && name !== TYPE ) {
				script.setAttribute( name, value );
			}
		} );

		const type = blocked.getAttribute( TYPE );
		if ( type !== null ) {
			script.type = type;
		}

		script.text = blocked.text;
		const isAsync = blocked.hasAttribute( 'async' );
		script.async = isAsync;
		const loaded = new Promise< void >( ( resolve ) => {
			if ( script.src === '' || isAsync ) {
				resolve();
				return;
			}

			script.addEventListener( 'load', () => resolve() );
			script.addEventListener( 'error', () => resolve() );
		} );
		blocked.replaceWith( script );

		return loaded;
	}
}
