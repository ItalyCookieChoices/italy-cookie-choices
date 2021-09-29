=== Italy Cookie Choices (for EU Cookie Law & Cookie Notice) ===
Contributors: overclokk, andreapernici, cardinaleandrea
Donate link: 
Tags: cookie notice, cookie law, cookie choices, cookies, eu cookie law, eu law, eu privacy directive, privacy, privacy directive, notification, privacy law, cookie law banner, implied consent, third party script, third party cookie, compliance
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.0
Stable tag: 2.5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The most complete cookie consent to easily comply with the european cookie law, display cookie notice and block third party cookie without degrading website performance.

Italy Cookie Choices allows you to easily comply with the european cookie law, showing
a notice to the user only the very first time that visits your website without degrading website performance and remove all the occurence of third party embed inside your site until consent, fully customizable also with your custom css.

Plugin requirement PHP >= 7.4 and WordPress >= 5.0

== Description ==

The Cookie Law is one of the most stupid law in the world. Maybe made by someone, who doesn't really understand how the web works. 

Italy Cookie Choices allows you to easily comply with the european cookie law (and Italian too), showing a notice to the user only the very first time he visits your website.
Message, position and the style can be easily modified through the plugin menu.

The plugin will not add any CSS stylesheet or Javascript file but only inline script, so it doesn't make your website slower.

Now you can block all cookies with new Third Part Cookie Eraser, activated it and let me now if you have any issue

= What you can do = 

* Display banner in front-end
** Three template (standard, big buttons, small buttons)
** Three position (top, dialog, bottom)
** Or custom style
* Content banner customizable with HTML tags
* URL and buttons text customizable
* Acceptance on mouse scroll
* Acceptance on second view
* Block third party script in page
* Add custom script to not block (all page)
* Add custom script to block (wp_header and wp_footer)
* Multilanguage (WPML, Polylang, Ceceppa)

[For more informations read the documentation](https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki)

Please, notify any issue on github repository https://github.com/ItalyCookieChoices/italy-cookie-choices/issues

= Anonymize Analytics IP =

Add `ga('set', 'anonymizeIp', true);` in your analytics snippet [AnonymizeIP](https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#anonymizeIp)

= Compatibilità W3TC & WP Super Cache fix =

https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki/Compatibilit%C3%A0-W3TC-fix

= Style customization =

https://github.com/ItalyCookieChoices/italy-cookie-choices/wiki/Style-customization

= Developer =

* [Enea Overclokk](http://www.overclokk.net/)
* [Andrea Pernici](https://www.andreapernici.com/)
* [Andrea Cardinale](https://www.andrea-cardinale.it/)

= Link =

* [WPItaly+](https://plus.google.com/u/0/communities/109254048492234113886)
* [Forum GT](http://www.giorgiotave.it/forum/)

= Translators = 

* Romanian: [Florin Lungu](http://www.florinlungu.it/)

== Installation ==

1. Upload `italy-cookie-choices` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin throught Settings -> Italy Cookie Choices

== Frequently Asked Questions ==

= How does it work? =

When a user visit your site, the plugin will check if this user has been already there, checking for a cookie that the plugin self creates (it is just a session cookie, so it is not against the law). If the user has not this cookie, a message will be shown (you can configure where, how and what will be show). If the users keeps surfing in your website, now or withing the next month, it means that he accepted the use of cookies in your website, so this message
will not be shown again.

= Why am I not seeing the message? =

Probably, you already have visited the site before, so you accepted the use of cookies and the plugin installed a cookie in your browser to know it. If you want to test if the plugin is working properly, try removing the cookies `displayCookieConsent` from your web browser or navigating in incognito mode. If you think that the plugin is not working properly, just open an issue here https://github.com/overclokk/italy-cookie-choices/issues and I will check it for you.

= Does it work in static HTML pages? =

Yes, simply copy the snippet generate by "Italy Cookie Choses for WordPress" and put in your HTML footer before </body> tag

= I use Async JS and CSS plugin, but the banner doesn't display =

You have to uncheck the `Detect <script> tags in wp_footer` in Async JS and CSS plugin settings page and refresh page.

= I don't see the banner in my site =

Try this way:
deactivate all plugin and try
if it works activate one pluigin at time and try
if it doesn't work activate standard theme and try

== Screenshots ==

1. Italy Cookie Choices Options Menu
2. Italy Cookie Choices top bar and embed block in front end
3. Italy Cookie Choices Options for Dialog box
4. Italy Cookie Choices Dialog Box in front end
5. Italy Cookie Choices Widget area
6. Italy Cookie Choices Content area

== Changelog ==

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