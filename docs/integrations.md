# Integrations

[Leggi in italiano](it/integrazioni.md).

How to connect a script, a service or a theme to the consent of Italy Cookie Choices.

The consent is stored in the browser, so the pages can be cached: every integration reads it in JavaScript, or lets the plugin block its scripts until the visitor accepts their category.

The categories are `necessary`, `preferences`, `statistics` and `marketing`. The necessary category is always accepted.

## Google Consent Mode v2

With **Integrations > Send the Consent Mode signals** on, the plugin prints a script at the start of the `<head>`, before the Google tags:

```js
gtag( 'consent', 'default', {
	security_storage: 'granted',
	functionality_storage: 'denied',
	personalization_storage: 'denied',
	analytics_storage: 'denied',
	ad_storage: 'denied',
	ad_user_data: 'denied',
	ad_personalization: 'denied',
	wait_for_update: 500,
} );
gtag( 'set', 'ads_data_redaction', true );
```

Then it sends `gtag( 'consent', 'update', { ... } )` with the stored consent, and again on every choice of the visitor.

Each category grants these consent types:

| Category | Consent types |
| --- | --- |
| `necessary` | `security_storage` |
| `preferences` | `functionality_storage`, `personalization_storage` |
| `statistics` | `analytics_storage` |
| `marketing` | `ad_storage`, `ad_user_data`, `ad_personalization` |

### Basic and advanced mode

In the **basic mode**, the default, Google Analytics and Google Tag Manager are blocked until the consent to their category, then they load with the consent already granted.

In the **advanced mode** they load before the consent and send cookieless pings until it is granted. Check that it is allowed where your visitors are: the Italian Garante requires the consent before any tracking.

### Map a custom category

```php
use ItalyCookieChoices\Integrations\Application\BuildingConsentMode;
use ItalyCookieChoices\Integrations\Domain\GoogleConsentType;

add_action( BuildingConsentMode::class, static function ( BuildingConsentMode $event ): void {
	$event->mapCategory( 'social', GoogleConsentType::AD_STORAGE, GoogleConsentType::AD_USER_DATA );
} );
```

## The tags printed by the plugin

The **Integrations** tab prints Google Analytics 4, Google Tag Manager and the Facebook pixel from their ids, each blocked until the consent to its category:

| Tag | Category |
| --- | --- |
| Google Analytics | `statistics` |
| Google Tag Manager | `marketing` |
| Facebook pixel | `marketing` |

Leave a field empty when the tag is printed by the theme or by another plugin. The plugin blocks those tags too, when they match a vendor of the **Blocking** tab.

The YouTube embeds use `youtube-nocookie.com` with **Use the privacy-enhanced mode of the YouTube embeds**, they still wait for the marketing consent.

## Block a script

### An enqueued script

Give the script a category, it is printed with `type="text/plain"` and runs after the consent:

```php
wp_enqueue_script( 'my-analytics', 'https://analytics.example.com/script.js', [], '1.0', true );
wp_script_add_data( 'my-analytics', 'consent_category', 'statistics' );
```

Its inline scripts, added with `wp_add_inline_script()`, wait with it.

### A script in the content or in a template

Write it already blocked, the plugin runs it after the consent:

```html
<script type="text/plain" data-category="statistics">
	window.myTracker.start();
</script>
```

### A vendor

A vendor is blocked wherever its scripts and frames are, by the hosts it loads from and by the text of its inline scripts:

```php
use ItalyCookieChoices\Blocking\Application\BuildingVendors;
use ItalyCookieChoices\Blocking\Domain\Vendor;

add_action( BuildingVendors::class, static function ( BuildingVendors $event ): void {
	$event->addVendor( new Vendor( 'matomo', 'statistics', [ 'matomo.example.com' ], [ '_paq.push' ] ) );
	$event->removeVendor( 'vimeo' );
	$event->allow( 'youtube.com/embed/public-video' );
} );
```

The site owner can add the same vendors from the **Blocking** tab, without code.

## Read the consent

### In JavaScript

```js
window.italyCookieChoices.hasConsent( 'statistics' );
window.italyCookieChoices.getConsent(); // { id, categories, version, date } or null

document.addEventListener( 'italy-cookie-choices:consent-changed', ( event ) => {
	const consent = event.detail.consent; // null when it is withdrawn
} );

document.addEventListener( 'italy-cookie-choices:ready', ( event ) => {
	const consent = event.detail.consent;
} );
```

A button can accept a category, or open the preferences:

```html
<button type="button" data-icc-accept="marketing">Show the video</button>
<button type="button" data-icc-open-preferences>Cookie preferences</button>
```

### In PHP

```php
if ( apply_filters( 'italy_cookie_choices_has_consent', false, 'statistics' ) ) {
	// The visitor accepted the statistics cookies.
}
```

The PHP check reads the cookie of the request: use it only on the pages that are not cached.

## Change the policy and the texts

```php
use ItalyCookieChoices\Consent\Application\BuildingConsentPolicy;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\Consent\UI\BuildingBanner;

add_action( BuildingConsentPolicy::class, static function ( BuildingConsentPolicy $event ): void {
	$event->addCategory( new Category( 'social' ) );
	// A new version asks the consent again.
	$event->changeVersion( new PolicyVersion( '2' ) );
} );

add_action( BuildingBanner::class, static function ( BuildingBanner $event ): void {
	$event->changeCategoryLabel( 'social', 'Social networks', 'Share buttons and embedded posts.' );
} );
```

## Translate the texts with another multilingual plugin

WPML and Polylang translate the saved texts out of the box. Other plugins, like qTranslate-XT, translate them with `TranslatingText`:

```php
use ItalyCookieChoices\Settings\TranslatingText;

add_action( TranslatingText::class, static function ( TranslatingText $event ): void {
	$event->changeText( qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $event->text() ) );
} );
```
