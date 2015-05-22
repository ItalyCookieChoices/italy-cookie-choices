=== Italy Cookie Choices ===
Contributors: overclokk
Donate link: 
Tags: compliance, cookie law, cookies, eu cookie law, eu law, eu privacy directive, privacy, privacy directive, notification, privacy law, cookie law banner, implied consent
Requires at least: 3.8
Tested up to: 4.3.0
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Italy Cookie Choices allows you to easily comply with the european cookie law, showing
a notice to the user only the very first time that visits your website without degrading website performance.

== Description ==

Italy Cookie Choices allows you to easily comply with the european cookie law, showing
a notice to the user only the very first time that visits your website.
Message, position and the style can be easyly modified through the plugin menu. 

The plugin will not add any CSS stylesheet or Javascript file but only inline script, what does not make your web site load more slowly.

Notify any issue on github repository https://github.com/overclokk/italy-cookie-choices/issues

== Installation ==

1. Upload `italy-cookie-choices` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin throught Settings -> Italy Cookie Choices

== Frequently Asked Questions ==

= How does it work? =

When a user visit your site, the plugin will check if this user has been already there, checking for a cookie that the plugin self creates (it is just a session cookie, so it is not against the law). If the user has not this cookie, a message will be shown (you can configure where, how and what will be show). If the users keeps surfing in your web, now or withing the next month, it means that he accepted the use of cookies in your website, so this message
will not be shown again.

= Why am I not seeing the message? =

Probably, you already have visited the site before, so you accepted the use of cookies and the plugin installed a cookie in your browser to know it. If you want to test if the plugin is working properly, try removing the cookies `displayCookieConsent` from your web browser or navigating in incognito mode. If you think that the plugin is not working properly, just open an issue here https://github.com/overclokk/italy-cookie-choices/issues and I will check it for you.

== Screenshots ==

1. Italy Cookie Choices Options Menu
2. Italy Cookie Choices top bar in front end
3. Italy Cookie Choices Options for Dialog box
4. Italy Cookie Choices Dialog Box in front end

== Changelog ==

= 1.1.2 =
Release Date: 

Dev time: 10min

* Fix z-index issue

= 1.1.1 =
Release Date: 

Dev time: 1h

* Add scroll event

= 1.1.0 =
Release Date: 

Dev time: 10h

* Fix special char visualization
* Add italian translations
* Removed default text

= 1.0.0 =
Release Date: May 21st, 2015

Dev time: 50h

* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.

== Translations ==
 
* English: default, always included.
* Italian: Italiano, sempre incluso.

*Note:* This plugins is localized/ translateable by default. This is very important for all users worldwide. So please contribute your language to the plugin to make it even more useful. For translating I recommend the awesome ["Codestyling Localization" plugin](http://wordpress.org/extend/plugins/codestyling-localization/) and for validating the ["Poedit Editor"](http://www.poedit.net/).
 
== Additional Info ==

**Idea Behind / Philosophy:** Italy Cookie Choices allows you to easily comply with the european cookie law, showing
a notice to the user only the very first time that visits your website without degrading website performance..
 
== Credits ==

* [www.cookiechoices.org](https://www.cookiechoices.org/)