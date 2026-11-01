# Upgrading from 2.x

The version 3.0 rewrites the plugin to meet the current consent rules: the ePrivacy Directive, art. 122 of the Italian Codice Privacy with the 2021 guidelines of the Garante, and the GDPR consent requirements.

Make a backup of the site and test the update on a staging site first.

## Name and menu

The plugin is now named **Cookie Choices**, it works in every EU country. Its settings page moves from **Settings** to its own **Cookie Choices** menu, the old address opens the new page.

## Requirements

* WordPress 6.5 or later, it was 6.1.
* PHP 8.2 or later, it was 7.4.

## Your settings

The settings are migrated on the first request after the update:

* The texts, the position, the template, the colors, the custom CSS and classes, the policy link and the excluded pages are kept.
* The blocked lists and the snippets of the 2.x textareas become a custom vendor named **Custom**, in the marketing category. Change its category in **Blocking** if its scripts are statistics.
* The allowed lists become **Never blocked**.
* A 2.x snippet with the wildcard `<---------SOMETHING--------->` inside the tag cannot be migrated as it was: only its longest piece of code is kept. Check the custom vendor after the update.
* The **Consent log** is turned on with a 12 months retention.
* The **Google Consent Mode v2** signals are turned on.
* The text of the 2.x button that closed the banner, like "Close", is not kept: the button now accepts every category, and shows "Accept all" in the language of the site. Write a new text in **Banner > Accept button text** if you need one.

The 2.x settings files can still be imported from **Tools**.

## The visitors give the consent again

The consent is now stored in a new cookie, `icc_consent`, with the accepted categories. The visitors who accepted with a 2.x version see the banner again, because their consent had no categories.

The 2.x cookie, `displayCookieConsent` by default, is still written while every category is accepted.

## What changed for the visitors

* The banner has three buttons with the same importance: accept all, reject all and preferences. Closing it keeps only the necessary cookies.
* The consent is given by category: necessary, preferences, statistics and marketing.
* The consent on scroll and on the second page view are removed, they are not a valid consent. Rewrite a banner text that says the visitor consents by scrolling or by going on.
* The choice lasts six months, it was one year.
* The visitors can change or withdraw the consent from the preferences, opened by the **Cookie preferences button** block, the `[cookie_preferences]` shortcode or a link to `#icc-preferences`. The `[delete_cookie]` shortcode prints the same button.

## What changed for the blocking

* The common vendors are always blocked in the content, in the widgets and in the embeds, the 2.x options to turn on the blocking are removed.
* The compatibility mode, **Block the vendors in the whole page**, blocks only the scripts of the vendors. The 2.x version blocked every script in the page.
* The blocked content shows a placeholder with a button that accepts its category.

## Markup and styles

The banner markup is new, it uses the `icc-banner` and `icc-preferences` classes. A custom CSS written for the 2.x markup, like `#cookieChoiceInfo`, must be updated. The custom classes of the settings are still added.

## Multilingual sites

The texts are registered in WPML and Polylang every time an administrator opens the dashboard, Polylang now translates them. Ceceppa Multilingua is no longer supported.

## For the developers

* The `icc_js_array` and `icc_output_html` filters are removed, use the `BuildingVendors` event to change what is blocked. See [Integrations](integrations.md).
* The `overclokk/cookie` dependency is removed.
* The `icc_plugin_loaded` action still fires, after the new `PluginLoaded` event.
* The `icc_bots` filter is deprecated, the bots are no longer detected. The `[accept_button]` shortcode is printed for every visitor.
* The consent is read in the browser with `window.italyCookieChoices`, or in PHP with the `italy_cookie_choices_has_consent` filter.
* An empty cookie name or value in the settings is rejected with an error, the 2.x versions restored the default.
