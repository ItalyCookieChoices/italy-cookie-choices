=== Italy Cookie Choices (for EU Cookie Law & Cookie Notice) ===
Contributors: overclokk, andreapernici, cardinaleandrea
Donate link: 
Tags: cookie, gdpr, cookie consent, cookie banner, consent mode
Requires at least: 6.5
Requires PHP: 8.2
Tested up to: 7.1
Stable tag: 3.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Ask the cookie consent by category and block third party scripts, videos and maps until the consent. With a consent log and Google Consent Mode v2.

== Description ==

Italy Cookie Choices helps you comply with the EU cookie rules: the ePrivacy Directive, art. 122 of the Italian Codice Privacy with the 2021 guidelines of the Garante, and the GDPR consent requirements.

The banner asks the consent by category, with three buttons of the same importance: accept all, reject all and preferences. The scripts, the videos and the maps of the third parties wait for the consent to their category. The consent is read in the browser, so the plugin works with the cached pages.

= What you can do =

* Show a banner at the top, at the bottom or in a modal window, with your text, colors and CSS
* Let the visitors choose the categories: necessary, preferences, statistics and marketing
* Block Google Analytics, Google Tag Manager, YouTube, Vimeo, Google Maps, Facebook, Instagram, X, LinkedIn, TikTok, Spotify, SoundCloud and Hotjar until the consent, and add your own vendors
* Show a placeholder with a button to accept the category of the blocked videos and maps
* Let the visitors change or withdraw the consent with a block, a shortcode or a link
* Log the consents to demonstrate them, without IP addresses, and export the log as CSV
* Send the Google Consent Mode v2 signals, in the basic or advanced mode
* Print Google Analytics, Google Tag Manager and the Facebook pixel from their ids
* Preview the banner while you change the settings
* Translate the texts with WPML and Polylang, the plugin is translated in Italian
* Import the settings of the 2.x versions

= Documentation =

* [Getting started](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/getting-started.md)
* [Blocking the third parties](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/blocking.md)
* [Consent log](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/consent-log.md)
* [Multilingual sites](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/multilingual.md)
* [Integrations, for the developers](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/integrations.md)
* [Upgrading from 2.x](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/upgrade-3.0.md)
* [Documentazione in italiano](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/it/README.md)

Please report the issues on [GitHub](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues).

This plugin provides technical tools, not legal advice. Check with your consultant what your site needs.

= Developers =

* [Enea Overclokk](http://www.overclokk.net/)
* [Andrea Pernici](https://www.andreapernici.com/)
* [Andrea Cardinale](https://www.andrea-cardinale.it/)

= Translators =

* Romanian: [Florin Lungu](http://www.florinlungu.it/)

== Installation ==

1. Install the plugin from **Plugins > Add New**, or upload the `italy-cookie-choices` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Configure it in **Settings > Italy Cookie Choices** and turn on **Show the banner**

== Frequently Asked Questions ==

= I updated from 2.x, what changes? =

Your settings are migrated, but the visitors give the consent again, because the 2.x consent had no categories. Read [Upgrading from 2.x](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/upgrade-3.0.md) before you update, and test the update on a staging site first.

= Why am I not seeing the banner? =

Check that **Show the banner** is on, then open the site in a private window: you probably already gave your consent. The banner does not open by itself on the policy page and on the excluded pages.

= How can the visitors change their choice? =

Add the **Cookie preferences button** block, the `[cookie_preferences]` shortcode or a link to `#icc-preferences` to the footer or to the cookie policy page. It opens the preferences, where the visitor can change or withdraw the consent.

= A script of my theme is not blocked =

The plugin blocks the vendors in the content, in the widgets, in the embeds and in the scripts added with `wp_enqueue_script()`. Add the vendor in **Blocking > Custom vendors**, or turn on **Block the vendors in the whole page** for the scripts printed directly in the page.

= Does it work with the cache plugins? =

Yes. The banner and the blocked content are the same for every visitor, the consent is read in the browser.

= Is the consent log personal data? =

The log has a random id created by the browser, the accepted categories, the policy version and the date, without IP addresses. The plugin suggests a text for your privacy policy in **Settings > Privacy**.

= How do I check the consent in my code? =

In JavaScript use `window.italyCookieChoices.hasConsent( 'statistics' )`, in PHP `apply_filters( 'italy_cookie_choices_has_consent', false, 'statistics' )`. Read [Integrations](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/integrations.md) for the events, the vendors and the Consent Mode.

== Screenshots ==

1. The settings page, with the preview of the banner
2. The names and descriptions of the categories, with the preview of the preferences
3. The vendors blocked until the consent
4. Google Consent Mode v2, the Google and Facebook tags and the YouTube embeds
5. The banner at the bottom of a page
6. The preferences, to choose the categories
7. The placeholder of a blocked video

== Changelog ==

= 3.0.0 =

A rewrite of the plugin for the current consent rules. Read [Upgrading from 2.x](https://github.com/ItalyCookieChoices/italy-cookie-choices/blob/master/docs/upgrade-3.0.md) before you update.

* Requires PHP 8.2 and WordPress 6.5
* New banner with accept all, reject all and preferences buttons of the same importance, accessible from the keyboard and the screen readers
* Consent by category: necessary, preferences, statistics and marketing, stored for six months in the new `icc_consent` cookie
* Removed the consent on scroll and on the second page view, they are not a valid consent
* The common vendors are always blocked until the consent, without buffering the page, with a placeholder for the blocked videos and maps
* Custom vendors with their category, and the addresses never blocked
* The visitors change or withdraw the consent with the Cookie preferences button block, the `[cookie_preferences]` shortcode or a link to `#icc-preferences`, the `[delete_cookie]` shortcode works again
* Consent log, without IP addresses, with a retention period and the CSV export
* Google Consent Mode v2, basic or advanced, and the Google Analytics, Google Tag Manager and Facebook pixel tags from their ids
* New settings page with a live preview, the import and the export of the settings
* WPML and Polylang translate the texts again, Italian translation included
* Removed the `icc_js_array` and `icc_output_html` filters and the Ceceppa Multilingua support

= 2.6.0 =
Release Date: Oct 21st, 2026

* Requires PHP 7.4 and WordPress 6.1, tested up to WordPress 7.1
* Updated all the dependencies, now prefixed to avoid conflicts with other plugins and themes that use the same packages
* Added integration and end to end tests, static analysis and continuous integration to prepare the refactor

= 2.5.3 =
Release Date: Sep 29th, 2021

Dev time: 2h

LATEST VERSION FOR PHP 5.3

* [This fixes issue #212](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/212)
* And it's time to start a refactoring for this plugin, too much legacy code and too much shitty code

= 2.5.2 =
Release Date: November 13rd, 2018

Dev time: 30m

* This is only an update for the upcoming WordPress 5.0
  No changes are made.

= 2.5.1 =
Release Date: Dicember 4th, 2016

Dev time: 4h

* Fixed server error 500 on php7 if advanced-cache.php doesn't exist.

= 2.5.0 =
Release Date: November 13th, 2016

Dev time: 4h

* Code refactoring for psr-4

= 2.4.5 =
Release Date: October 9th, 2016

Dev time: 1h

* [Merge the fix #155](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/155)

= 2.4.4 =
Release Date: January 6th, 2016

Dev time: 10m

* [Fixed Fatal error: Class 'CMLTranslations' not found](https://wordpress.org/support/topic/non-memorizza-la-configurazione?replies=1#post-7851297)

= 2.4.3 =
Release Date: January 5th, 2016

Dev time: 10h

* Fixed Ceceppa Multilingua string translations bug [#152](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/152) [and](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/124#issuecomment-122952940)

= 2.4.2 =
Release Date: December 29th, 2015

Dev time: 1h

* Fixed PHP7 Fatal error [#149](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/149)
* Walking to WPCS

= 2.4.1 =
Release Date: November 6th, 2015

Dev time: 50h

* Improved UI for selecting block/allow script
* [Added new check for bot Crawler](https://wordpress.org/support/topic/non-trattare-i-bot-come-umani?replies=2)

= 2.4.0 =
Release Date: August 31th, 2015

Dev time: 11h

* Added import/export functionality
* Maybe fixed [#133](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/133)
* Fixed PHP check for lang functions [#128](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/128)

= 2.3.5 =
Release Date: July 25th, 2015

Dev time: 3h

* [Add filter for js_array `icc_js_array`](https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki/Filters-Hook#modificare-larray-con-tutti-gli-embed-trovati-disponibile-dalla-versione-235)
* Fixed performance issue in sites with many posts
* [Fixed Parse error issue #124](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/124#issuecomment-122952940)


= 2.3.4 =
Release Date: July 22th, 2015

Dev time: 10min

* [Fixed bug in plugin admin panel](https://wordpress.org/support/topic/il-menu-impostazioni-non-si-carica-completamente?replies=3#post-7207319)

= 2.3.3 =
Release Date: July 21th, 2015

Dev time: 2h

* Fixed [#126](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/126) and [#103](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/103) for Sitemap and feed page

= 2.3.2 =
Release Date: July 17th, 2015

Dev time: 1h

* [Fixed warning array_merge on multisite](https://github.com/ItalyCookieChoices/italy-cookie-choices/commit/f2797a4f63a247a4918ec1c8d0823fc07b50384e)

= 2.3.1 =
Release Date: July 13th, 2015

Dev time: 3h

* Added link to setting page and docs in plugins panel
* Fixed issue [#118](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/118)

= 2.3.0 =
Release Date: July 11th, 2015

Dev time: 50h

* Added jQuery autocomplete in slug and url input
* Added text editor and allowed HTML tags for short policy text area input and the blocked embed text
* Fixed the insertion of the SOMETHING button in textarea
* Added delete selected text for SOMETHING button in textarea
* Fixed !match with 'REQUEST_URI' [here](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/111) and [here](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/68)
* [Fixed #119](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/119)
* [Added multilanguage also to the blocked embed text and button](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/118)
* [Added multilang for slug](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/115)
* Improved sanitization for multilang string


= 2.2.2 =
Release Date: July 1st, 2015

Dev time: 3h

* [Fixed browser issue](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/113)

= 2.2.1 =
Release Date: July 1st, 2015

Dev time: 10h

* Fixed text position for templates
* [Fixed scroll event in case slug is not set](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/101)
* [Fixed W3C validator error](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/96)
* Added new button below the textarea for separator `<---------SOMETHING--------->` & `<---------SEP--------->`
* Fixed uninstall issue in uninstall.php
* Improved UX for slug input

= 2.2.0 =
Release Date: June 24th, 2015

Dev time: 100h

* New file structure
* Removed scroll event on cookie policy page
* Fixed compatibility to pre WP 4.1
* Added scripts allowed in body
* [Added dumb regex for custom javascript](https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki/Custom-script#come-evitare-che-vengano-bloccati-script-che-non-installano-cookie-slider-ecc) `<script src="/assets/js/<---------SOMETHING--------->.js" ></script>`
* [Fixed dialog off-screen in mobile devices](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/24)
* [Fixed typo](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/62)
* [Fixed fallback color for Text & BG in banner](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/95)
* [Added multilingual support only for banner](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/31)
* [Added uninstall.php](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/34)
* Added text area for custom CSS
* [Added input for custom CSS class](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/16)
* [Added filter `icc_output_html` on snippet output for developer](https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki/Filters-Hook#modificare-loutput)

= 2.1.0 =
Release Date: June 5th, 2015

Dev time: 100h

* Added new third part cookie eraser functionality (much more powerful :-))
* New options for select banner template
* Added Bottom bar style
* [Fixed scroll issue](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/68)
* New options for accepting on second view
* Fixed some issue
* [Now compatibility with PHP 5.3.29](https://github.com/ItalyCookieChoices/italy-cookie-choices/issues/42)

= 2.0.0 =
Release Date: June 5th, 2015

Dev time: 60h

* Merged Third part cookie eraser and Italy Cookie Choices
* Added new input for banner background color and text color
* Added input for text and button in embed block content and widget
* Added shortcode button for policy page [accept_button]

= 1.2.3 =
Release Date: May 31th, 2015

Dev time: 3h

* Added slug input for your cookie policy page to display only topbar
* Added option for target _blank

= 1.2.2 =
Release Date: May 29th, 2015

Dev time: 1h

* [Fixed cookie setting path to root](https://github.com/overclokk/italy-cookie-choices/issues/20)

= 1.2.1 =
Release Date: May 28th, 2015

Dev time: 2h

* [Added checkbox for refresh page after button click](https://wordpress.org/support/topic/aggiunta-del-refresh-dopo-lok?replies=3)
* [Fixed cookie name issue](https://github.com/overclokk/italy-cookie-choices/issues/21)


= 1.2.0 =
Release Date: May 27th, 2015

Dev time: 20h

* [Fixed duble alert box in admin when options are saved](https://github.com/overclokk/italy-cookie-choices/issues/13)
* [Add options for mouse scroll event](https://github.com/overclokk/italy-cookie-choices/issues/19)
* [Added options for add margin at top of the page if topbar is used](https://github.com/overclokk/italy-cookie-choices/issues/6)
* Added advanced options for set Cookie name and Cookie value for an eventual migration to this plugin

= 1.1.3 =
Release Date: May 26th, 2015

Dev time: 5h

* Fixed admin UX https://github.com/overclokk/italy-cookie-choices/issues/14
* Fixed Typo in lang
* Changed WP compatibility to 4.1
* Added noscript in case browser has JavaScript disabled https://github.com/overclokk/italy-cookie-choices/issues/8
* Fixed click event https://github.com/overclokk/italy-cookie-choices/issues/15

= 1.1.2 =
Release Date: May 22st, 2015

Dev time: 10min

* Fix z-index issue

= 1.1.1 =
Release Date: May 22st, 2015

Dev time: 1h

* Add scroll event

= 1.1.0 =
Release Date: May 22st, 2015

Dev time: 10h

* Fix special char visualization
* Add italian translations
* Removed default text

= 1.0.0 =
Release Date: May 21st, 2015

Dev time: 50h

* First release.

== Upgrade Notice ==

= 3.0.0 =
A rewrite for the current consent rules. Requires PHP 8.2 and WordPress 6.5. The visitors give the consent again. Read the upgrade notes and test on a staging site first.

= 2.6.0 =
Requires PHP 7.4 and WordPress 6.1, please check your server before updating

= 2.2.1 =
Fixed some issue, see the changelog for more informations

= 2.1.0 =
More functionality added, please, see the plugin's admin page

= 2.0.0 =
New: Third part cookie eraser

= 1.0.0 =
First release.

== Translations ==
 
* English: default, always included.
* Italian: Italiano, sempre incluso.
* Romanian: Română, inclus.

*Note:* This plugins is localized/ translateable by default. This is very important for all users worldwide. So please contribute your language to the plugin to make it even more useful. For translating I recommend the awesome ["Codestyling Localization" plugin](http://wordpress.org/extend/plugins/codestyling-localization/) and for validating the ["Poedit Editor"](http://www.poedit.net/).
 
== Additional Info ==

**Idea Behind / Philosophy:** Italy Cookie Choices allows you to easily comply with the european cookie law, showing
a notice to the user only the very first time that visits your website without degrading website performance..
 
== Credits ==

* [www.cookiechoices.org](https://www.cookiechoices.org/)
* [Loghino piccolo](http://www.posizionamento-seo.com/)
* Romanian lang: [Florin Lungu](http://www.florinlungu.it/)
* [Some code comes from the WordPress Plugin Boilerplate Powered by Mte90](https://github.com/Mte90/WordPress-Plugin-Boilerplate-Powered)
