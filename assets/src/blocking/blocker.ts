import type { ConsentApi } from '../consent/types';

/** Printed by ItalyCookieChoices\Blocking\Infrastructure\HtmlBlocker, or by hand. */
const BLOCKED_SCRIPTS = 'script[type="text/plain"][data-category]';
const BLOCKED_FRAMES =
	'iframe[data-icc-src][data-category], embed[data-icc-src][data-category]';
const SOURCE = 'data-icc-src';
const TYPE = 'data-icc-type';
const HIDDEN = 'data-icc-hidden';
const PLACEHOLDERS = 'template[data-icc-placeholder]';
const PREFERENCES_LINK = '.icc-placeholder__preferences';

/**
 * Runs the blocked scripts and loads the blocked frames once their category has the consent.
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
		this.scripts = this.scripts.then( () => this.runScripts() );

		return this.scripts;
	}

	private updateFrame( frame: Element ): void {
		const category = frame.getAttribute( 'data-category' ) ?? '';
		if ( ! this.api.hasConsent( category ) ) {
			frame.removeAttribute( 'src' );
			this.showPlaceholder( frame, category );
			return;
		}

		this.placeholders.get( frame )?.remove();
		this.placeholders.delete( frame );
		frame.removeAttribute( HIDDEN );
		const source = frame.getAttribute( SOURCE ) ?? '';
		if ( frame.getAttribute( 'src' ) !== source ) {
			frame.setAttribute( 'src', source );
		}
	}

	private showPlaceholder( frame: Element, category: string ): void {
		if ( this.placeholders.has( frame ) ) {
			return;
		}

		const placeholder = this.placeholderOf( category );
		if ( placeholder === null ) {
			return;
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

	private async runScripts(): Promise< void > {
		const scripts = Array.from(
			this.document.querySelectorAll< HTMLScriptElement >(
				BLOCKED_SCRIPTS
			)
		).filter( ( script ) =>
			this.api.hasConsent( script.dataset.category ?? '' )
		);

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
