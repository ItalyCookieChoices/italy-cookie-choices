# Import from another plugin

[Leggi in italiano](it/migrazione.md).

Cookie Choices imports the settings of another consent plugin: the banner, the links, the categories, the vendors and the cookies. It reads them in the database, so the other plugin can be active or deactivated, not deleted.

## The plugins

* Complianz, the free version 7 and the versions before
* CookieYes, version 3 and the versions 2, named GDPR Cookie Consent
* Cookie Notice & Compliance, the banner of the plugin and what the Compliance app keeps on the site
* GDPR Cookie Compliance, by Moove Agency, the free version
* Borlabs Cookie, version 3
* Real Cookie Banner, the free version 5
* CookieAdmin, the free version
* WPConsent, the free version

The other plugins come in the next versions. A developer adds one with the `BuildingMigrationSources` event, see [Add a plugin](#add-a-plugin).

## Import

When the settings of a plugin are in the database and there is something to import, the dashboard shows a notice. **Import the settings** opens the **Tools** tab, under **Import from another plugin**:

1. **Preview** lists what the import changes, and what to check after it. Nothing is saved.
2. **Import** saves the settings.
3. Deactivate the other plugin, or the site shows two banners. Its settings stay, the import can be repeated.

**Dismiss** hides the notice. From the terminal, see [WP-CLI](wp-cli.md#migrate).

## What changes

* The settings the other plugin has replace the ones of Cookie Choices, the others stay.
* The lists get the new entries and keep theirs: the custom vendors, the scripts after the consent, the addresses never blocked and the site cookies.
* The categories keep the texts the other plugin has not.
* The common vendors the other plugin uses are turned on, the others stay as they are.
* A feature the other plugin leaves off keeps the default of Cookie Choices: the import turns features on, not off.
* The visitors are asked for the consent again: the consent given in the other banner is not carried over.

## From Complianz

| Complianz | Cookie Choices |
| --- | --- |
| The texts and the buttons of the default banner | The banner text and the button texts |
| The position, the logo and the colors of the banner | The **Style** tab |
| The custom CSS | The custom CSS, in a comment: its selectors are the ones of Complianz |
| The categories | The texts of the categories, Functional becomes Necessary |
| The cookie policy and the Impressum | The policy and legal notice links |
| The consent expiration | The closest duration of the consent |
| The cookie domain, the pages without the banner, Do Not Track and Global Privacy Control | The same settings |
| The Manage consent button | The floating button |
| The consent per service | The consent per vendor |
| Google Consent Mode, the Google Analytics and Tag Manager ids | The **Integrations** tab |
| The services and the social networks | The common vendors with the same name |
| The blocked scripts of the script center | Custom vendors |
| The added scripts of the script center | The scripts after the consent |
| The whitelisted scripts | The addresses never blocked |
| The cookies of the site | The site cookies |
| The cookies of a blocked script | The cookies of its custom vendor |

Not imported: the consent on scroll or after a time, it is not a valid consent; the regions and the banner for the United States; TCF; the documents Complianz generates, the link to the page is kept; the translations. The preview lists the ones the site uses.

## From CookieYes

| CookieYes | Cookie Choices |
| --- | --- |
| The texts and the buttons of the active banner, in the default language | The banner text and the button texts |
| The privacy link and its Read more button | The policy link, its new tab and nofollow |
| The type, the position, the logo and the colors of the banner | The **Style** tab, the popup becomes the modal |
| The custom CSS | The custom CSS, in a comment: its selectors are the ones of CookieYes |
| The categories | The texts of the categories: Functional becomes Preferences, Analytics and Performance become Statistics, Advertisement becomes Marketing |
| The consent expiration, the reload on accept and Global Privacy Control | The same settings |
| The revisit consent button | The floating button |
| The title of the video placeholder | The placeholder text of the blocked content |
| Google Consent Mode | The **Integrations** tab |
| The cookies with a script pattern of a common vendor | That common vendor, turned on |
| The cookies with another script pattern | A custom vendor for each pattern, with its cookies |
| The cookies without a script pattern | The site cookies |

The versions 2 import the texts, the Read more link, the position, the colors, the revisit button and the cookies, as site cookies.

Not imported: the banner for the United States with the opt-out, the geo-targeting, the consent on scroll, the translations. When the site uses the CookieYes web app, its settings in WordPress can be older than the ones of the web app: check them after the import.

## From Cookie Notice & Compliance

| Cookie Notice | Cookie Choices |
| --- | --- |
| The message, the accept and refuse button texts | The banner text and the button texts |
| The privacy policy link, a page or an address, and its target | The policy link and its new tab |
| The position and the colors of the bar | The **Style** tab |
| The cookie expiry | The closest duration of the consent |
| The reload after the accept | Reload the page after the choice |
| The revoke button | The floating button |
| The script blocking code, in the head and in the body | The scripts after the consent, in the marketing category |

Cookie Notice asks one consent for everything: its scripts wait for the marketing consent, change their category in **Integrations > Scripts after the consent** when they are statistics. With the Compliance app, the banner, its texts and its categories are in the app: the import brings the position, the color, the policy link, Global Privacy Control, Google Consent Mode and the custom providers, as custom vendors.

Not imported: the consent on scroll or on click, the conditional display rules, the translations.

## From GDPR Cookie Compliance

| GDPR Cookie Compliance | Cookie Choices |
| --- | --- |
| The text of the cookie banner and its button labels, the save button of the settings | The banner text and the button texts |
| The banner position, the color scheme and the company logo | The **Style** tab |
| The consent expiry | The closest duration of the consent |
| The force reload | Reload the page after the choice |
| The floating button and its label | The floating button, on the left |
| The tabs: strictly necessary, third party and additional cookies | The texts of the Necessary, Statistics and Marketing categories |
| The scripts of each tab, in the head, the body and the footer | The scripts after the consent, in the category of the tab |
| The Google Analytics 4, Tag Manager and Facebook pixel ids of the integrations | The **Integrations** tab |
| Google Tag Manager with Consent Mode | Google Consent Mode |
| Google Ads | The Google Ads common vendor, its tag is not imported |

The texts are read in the default language of WPML or Polylang. Not imported: the Google Ads tag, the translations, and the premium features.

## From Borlabs Cookie

| Borlabs Cookie | Cookie Choices |
| --- | --- |
| The texts of the dialog and its buttons | The banner text and the button texts |
| The privacy and imprint links, a page or an address | The policy and legal notice links |
| The pages where the dialog is hidden | The pages where the banner does not open by itself |
| The layout and position, the logo, the colors and the custom CSS of the dialog | The **Style** tab, the custom CSS in a comment |
| The cookie lifetime, the cookie domain, the reload after the opt-in | The same settings |
| The widget | The floating button |
| The hostnames excluded from the content blockers | The addresses never blocked |
| The service groups | The texts of the categories: Essential becomes Necessary, External Media becomes Marketing |
| The services of a common vendor, like Google Analytics or YouTube | That common vendor, turned on, with its Google and Facebook tag ids and Consent Mode |
| The other services with a location | Custom vendors with their cookies, their opt-in code as a script after the consent |
| The cookies of the essential services | The site cookies |

The texts are read in the language of the site. Not imported: the services without a location whose code needs Borlabs Cookie, the opt-out code, the geo-targeting, the IAB TCF, the translations. Borlabs Cookie is a paid plugin: the import reads its settings in the database, it does not use its code.

## From Real Cookie Banner

| Real Cookie Banner | Cookie Choices |
| --- | --- |
| The description of the banner and the texts of its buttons | The banner text and the button texts |
| The legal links of the privacy policy and of the legal notice, a page or an address | The policy and legal notice links |
| The pages of the legal links that hide the banner | The pages where the banner does not open by itself |
| The layout and position, the logo, the colors and the custom CSS | The **Style** tab, the custom CSS in a comment |
| The duration of the consent cookie | The duration of the consent |
| The sticky links | The floating button |
| The service groups | The texts of the categories: Functional becomes Preferences, the custom groups Marketing |
| The services of a common vendor, found by their template, code or blocker rules | That common vendor, turned on, with its Google and Facebook tag ids; the code of the other vendors as a script after the consent |
| The other services | Custom vendors with the address rules of their content blockers and their cookies, their opt-in code as a script after the consent |
| The cookies of the essential services | The site cookies |

The texts are read in the language of the site, with WPML or Polylang too. Only the published services and blockers are imported, the fields of the code, like `{{measurementId}}`, are filled. Not imported: the services without blocker rules whose code needs Real Cookie Banner, the HTML selectors of the blockers, the opt-out code, the headline, the texts of the content blockers, the IAB TCF, the consent forwarding and the country bypass.

## From CookieAdmin

| CookieAdmin | Cookie Choices |
| --- | --- |
| The notice and the texts of its buttons, of the GDPR banner | The banner text and the button texts |
| The privacy policy link, a page or an address | The policy link |
| The layout and position, the background and the text colors | The **Style** tab: the box on its side, the footer at the top or at the bottom, the pop-up in the middle |
| The days of the consent | The duration of the consent |
| Google Consent Mode, the advanced mode, the Clarity consent, the Global Privacy Control | The same settings |
| The YouTube, Vimeo, SoundCloud, Dailymotion and Google Maps embeds it blocks | Those common vendors, turned on |
| The cookies of its scans of a common vendor, like `_ga` | That common vendor, turned on |
| The other cookies of its scans, with their category | The site cookies, with their duration and description |

The texts left at the defaults of CookieAdmin are not saved by it, so the ones of Cookie Choices stay. Not imported: the titles, the texts of the preferences, the US laws, the custom icon and the cookies without a category.

## From WPConsent

| WPConsent | Cookie Choices |
| --- | --- |
| The banner on or off, its message and the texts of its buttons | The banner, its text and the button texts |
| The cookie policy page | The policy link |
| The layout and position, the logo, the background and the text colors | The **Style** tab: the long banner at the top or at the bottom, the floating one on its side, the modal in the middle |
| The duration of the consent | The same setting |
| Google Consent Mode, the Global Privacy Control | The same settings |
| The categories | The texts of the categories: Essential becomes Necessary |
| The services of a common vendor, like Google Analytics or Facebook Pixel | That common vendor, turned on |
| The cookies of the essential category and of the other services | The site cookies, with their duration and description |

WPConsent blocks the other services with its own list, not with addresses: add the addresses of their scripts as custom vendors, the import report lists them. Not imported: the texts of the preferences, the geolocation, the IAB TCF and the legal documents.

## Add a plugin

A source implements `ItalyCookieChoices\Migration\Domain\MigrationSource`: an id, a name, whether its settings are in the database and the settings in the shape of the option of Cookie Choices, with the names of the `*Setting` classes. A source that reads no setting and no vendor is not listed.

```php
add_action(
    \ItalyCookieChoices\Migration\Application\BuildingMigrationSources::class,
    static function ( $event ): void {
        $event->addSource( new MyPluginSource() );
    }
);
```

The imported settings are saved through the sanitizer of the settings, like the ones of the settings page.
