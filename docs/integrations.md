# Integrations

[Leggi in italiano](it/integrazioni.md).

How to connect a script, a service or a theme to the consent of Cookie Choices.

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

## Microsoft consent signals

With **Integrations > Send the consent to Microsoft Advertising (UET)** and **Send the consent to Microsoft Clarity** on, the default, the plugin prints a script at the start of the `<head>`, before the Microsoft tags. The calls wait in the `window.uetq` and `window.clarity` queues until the tags load.

Microsoft Advertising gets the ad storage denied by default, then the stored consent and every choice:

```js
window.uetq.push( 'consent', 'default', { ad_storage: 'denied' } );
window.uetq.push( 'consent', 'update', { ad_storage: 'granted' } );
```

Microsoft Clarity gets the stored consent, denied without one, then every choice, with its Consent API v2. Clarity requires it for the visitors from the EEA, the UK and Switzerland:

```js
window.clarity( 'consentv2', { analytics_Storage: 'granted', ad_Storage: 'denied' } );
```

| Category | UET | Clarity |
| --- | --- | --- |
| `statistics` | | `analytics_Storage` |
| `marketing` | `ad_storage` | `ad_Storage` |

## Amazon Consent Signal

From June 2026 Amazon Ads uses the data of the visitors from the EEA and the UK only with a consent signal. With **Integrations > Send the consent to Amazon Ads** on, the plugin prints at the start of the `<head>` the consent helper of Amazon, `amzn-consent.js`, and its `amznConsent()` call, blocked until the marketing consent. After the consent they run before the Amazon Ad Tag, `amzn.js`, with the ad storage and the user data granted. Without the consent nothing loads from Amazon: the **Amazon Ads** preset blocks the tag too.

Amazon asks the country of the visitor. The plugin does not locate the visitors: write the two letters of the country of your visitors in **Country of the visitors, for Amazon**, like `IT`, or leave it empty for the country of the language of the site. Turn the signal on only when the site uses the Amazon Ad Tag, the helper is a script of Amazon. When the banner uses the IAB TCF, Amazon reads the TCF signal instead.

## WP Consent API

When the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) plugin is active and the banner is on, Cookie Choices writes the consent to it, so the plugins that read it, like WooCommerce and Site Kit, respect the choice of the visitor. There is no setting.

Without a consent plugin the API allows every category. Cookie Choices declares the `optin` consent type, so before the choice only the functional category is allowed.

Each category allows these categories of the API:

| Category | Categories of the WP Consent API |
| --- | --- |
| `necessary` | `functional` |
| `preferences` | `preferences` |
| `statistics` | `statistics`, `statistics-anonymous` |
| `marketing` | `marketing` |

In the browser the plugin calls `wp_set_consent()` with the stored consent and on every choice, a rejection and a withdrawal included. In PHP `wp_has_consent()` answers from the consent cookie of Cookie Choices, so an expired consent is never allowed. The cookies of the API last as the consent.

### Map a custom category

```php
use ItalyCookieChoices\Integrations\Application\BuildingWpConsentApi;
use ItalyCookieChoices\Integrations\Domain\WpConsentCategory;

add_action( BuildingWpConsentApi::class, static function ( BuildingWpConsentApi $event ): void {
	$event->mapCategory( 'social', WpConsentCategory::MARKETING );
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

## Scripts after the consent

**Scripts after the consent**, in the **Integrations** tab, adds code to the pages after the consent to its category: a chat, a tracking snippet that no vendor covers, the code of a service that gives you an HTML snippet. Each script has a name, a category, a position, in the head or at the end of the body, and its code.

* The code is HTML: the scripts and the elements they need, like the image of a tracking pixel. Nothing of it loads before the consent, the image neither.
* The scripts run in their order, each script file is loaded before the next script runs. When the consent arrives later, they run without a reload.
* The code of the necessary category is printed as it is, on every page.
* A script that matches a vendor of the **Blocking** tab follows the consent to that vendor too, with the [consent per vendor](blocking.md#consent-per-vendor).
* After a withdrawal the code no longer runs from the next page, the [cookie cleanup](cookie-table.md#the-cookies-without-the-consent-are-deleted) deletes the cookies of the category.

Only the users who can save unfiltered HTML change the scripts, like the Custom HTML block: the administrators of a site, the super admins of a network, and WP-CLI. The other users see only their names, and saving the settings keeps the scripts as they are.

The code is printed with the plugin active, also without a vendor in the **Blocking** tab. Write the code of a vendor here, or block it where the theme prints it: not both.

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
// The category and, with the consent per vendor, the vendor not refused.
window.italyCookieChoices.hasVendorConsent( 'youtube', 'marketing' );
window.italyCookieChoices.getConsent(); // { id, categories, version, date, refusedVendors? } or null

document.addEventListener( 'italy-cookie-choices:consent-changed', ( event ) => {
	const consent = event.detail.consent; // null when it is withdrawn
} );

document.addEventListener( 'italy-cookie-choices:ready', ( event ) => {
	const consent = event.detail.consent;
} );
```

A button can accept a category, or a single vendor with `data-icc-vendor`, or open the preferences:

```html
<button type="button" data-icc-accept="marketing">Show the video</button>
<button type="button" data-icc-accept="marketing" data-icc-vendor="youtube">Show the YouTube video</button>
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
	// The note shown when the browser sends the Global Privacy Control signal.
	$event->changeText( BuildingBanner::GLOBAL_PRIVACY_CONTROL, 'Your browser sends GPC: marketing stays off.' );
} );
```

## When the plugin is loaded

```php
use ItalyCookieChoices\PluginLoaded;

add_action( PluginLoaded::class, static function ( PluginLoaded $event ): void {
	// The plugin is loaded and its services are hooked.
} );
```

The `icc_plugin_loaded` action of the 2.x versions still fires, after the event.

## Translate the texts with another multilingual plugin

WPML and Polylang translate the saved texts out of the box. Other plugins, like qTranslate-XT, translate them with `TranslatingText`:

```php
use ItalyCookieChoices\Settings\TranslatingText;

add_action( TranslatingText::class, static function ( TranslatingText $event ): void {
	$event->changeText( qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $event->text() ) );
} );
```
