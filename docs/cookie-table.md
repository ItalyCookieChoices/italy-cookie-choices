# Cookie table

[Leggi in italiano](it/tabella-cookie.md).

The cookie policy lists the cookies of the site. Add the **Cookie table** block, or the `[cookie_table]` shortcode, to the cookie policy page: the plugin prints a table for each category with cookies, with the name, the provider, the purpose and the duration of each cookie.

The table follows the settings, it changes when you turn a vendor on or off.

## What it lists

* The consent cookie of the plugin, `icc_consent`, in the necessary category.
* The cookies of the common vendors turned on in **Blocking > Common vendors**, as their documentation describes them.
* The cookies of the custom vendors: add them in **Blocking > Custom vendors**, with **Add a cookie**.
* The cookies of the site itself, like the login of WordPress or the cart of the shop: add them in **Blocking > Site cookies**, with their category.
* The cookies the other plugins register with `wp_add_cookie_info()` of the [WP Consent API](integrations.md#wp-consent-api), when it is active.

A name ending with `*` is a prefix: `_ga_*` is the cookie `_ga_` followed by the id of the property.

## The cookies of the common vendors

The names, the durations and the purposes come from the documentation of each vendor, from its scripts when the documentation lacks the durations, or from cookiedatabase.org. The vendors can change them: check the cookies of your site with the developer tools of the browser.

Google Tag Manager sets no cookie of its own. The embeds of Instagram, X and SoundCloud set their cookies on their own domains and do not document them, add them as site cookies if you need them.

## For the developers

Add or remove a cookie with `BuildingCookieTable`, for example the cookies of a theme:

```php
use ItalyCookieChoices\Cookies\Application\BuildingCookieTable;
use ItalyCookieChoices\Cookies\Domain\Cookie;

add_action( BuildingCookieTable::class, static function ( BuildingCookieTable $event ): void {
	$event->addCookie( new Cookie( 'theme_dark', 'My theme', 'preferences', 'Remembers the dark mode.', '1 year' ) );
	$event->removeCookie( 'test_cookie' );
} );
```

A cookie is listed once: the first one with a name wins.
