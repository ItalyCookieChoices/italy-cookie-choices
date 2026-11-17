# Blocking the third parties

The scripts, the frames and the embeds of the third parties are blocked until the visitor accepts their category. A blocked script is printed with `type="text/plain"` and runs after the consent, a blocked frame keeps its address in `data-icc-src` and loads after the consent, only when it is an `http:` or `https:` address.

The plugin blocks what WordPress prints in the content, in the widgets, in the embeds and with `wp_enqueue_script()`. The pages are not buffered, so the plugin works with the cache plugins.

## Vendors

A vendor is a third party, like YouTube, with the addresses it loads from and the texts of its inline scripts. The plugin knows these vendors:

| Vendor | Category |
| --- | --- |
| Google Analytics | Statistics |
| Hotjar | Statistics |
| Microsoft Clarity | Statistics |
| Matomo Cloud | Statistics |
| Google Tag Manager | Marketing |
| Google Ads | Marketing |
| Google AdSense | Marketing |
| Microsoft Advertising | Marketing |
| YouTube | Marketing |
| Vimeo | Marketing |
| Google Maps | Marketing |
| Facebook | Marketing |
| Instagram | Marketing |
| X (Twitter) | Marketing |
| LinkedIn | Marketing |
| TikTok | Marketing |
| Pinterest | Marketing |
| Snapchat | Marketing |
| Reddit | Marketing |
| HubSpot | Marketing |
| Spotify | Marketing |
| SoundCloud | Marketing |
| Google reCAPTCHA | Marketing, turned off |

In **Blocking > Common vendors** you can turn off a vendor the site does not need to block.

Google reCAPTCHA starts turned off: the forms that use it would wait for the marketing consent. Turn it on when the forms can wait. HubSpot blocks the tracking and the chat, not the forms of `js.hsforms.net`. Matomo Cloud blocks only the Matomo served by `matomo.cloud`, Matomo on your site can be set up without cookies.

## Scan the site

**Scan the site**, at the top of the **Blocking** tab, finds the vendors of your pages.

1. Choose what to scan: the home page, the post types, the categories, and single posts or pages searched by title.
2. Click **Scan the site**. The pages are read from the server in batches of ten, like a visitor that has not chosen yet, and the progress shows the pages scanned.
3. The list shows:
   * **Vendors found**: the common vendors of the pages, with the number of pages. Check **Block** to block one that is turned off.
   * **Unknown third parties**: the hosts of the scripts and frames that no vendor covers. **Add as a custom vendor** adds the host to the custom vendors, in the last category. Some hosts, like the libraries served by a CDN, set no cookies: add only the ones that do.
   * The pages that could not be read, with the reason.
4. Save the changes: the scan changes nothing by itself.

The scan reads the HTML of the pages: it finds the scripts, the frames and the tracking pixels, not the cookies that JavaScript sets in the browser. The cookies of the vendors come from their presets, see the cookie table. A selection scans 500 pages at most. When the host blocks the requests of the site to itself, like the Site Health of WordPress reports, the scan shows the error of each page.

## Google tag gateway

With the Google tag gateway the Google tags load from a path of your site, like `/metrics`, instead of `googletagmanager.com`. The plugin blocks them by the id in their address: `G-` and `GT-` wait for the statistics consent like Google Analytics, `AW-` and `DC-` wait for the marketing consent like Google Ads, `GTM-` waits for the marketing consent like Google Tag Manager. The inline loader of Google Tag Manager is blocked on any host too.

When the address of a tag has no id, write the path in **Integrations > Google tag gateway path**, like `/metrics`: every script and frame under it waits for the marketing consent.

## Custom vendors

**Add a vendor** for a third party the plugin does not know. Give it a name, a category and the texts to block: a part of the address, like `tracker.example.com/script.js`, or a text of its inline script, like `myTracker.start(`. Press Enter after each one.

## Never blocked

The addresses and the scripts the site needs, even when a vendor matches them. For example `youtube.com/embed/public-video`.

## Placeholders

![The placeholder of a blocked video](images/en_US-7-placeholder.png)

A blocked frame, like a video or a map, shows a placeholder with a button that accepts its category and a link to the preferences. **Placeholder text of the blocked content** and **Placeholder button text** replace the default texts, that contain the category name.

To hide the placeholders, add this rule to the **Custom CSS**:

```css
.icc-placeholder {
	display: none !important;
}
```

The blocked frame stays hidden until the consent, the visitors accept its category from the banner or the preferences.

## Block the vendors in the whole page

The scripts printed by the theme or by a plugin directly in the page, without the WordPress functions, are not blocked by default. **Block the vendors in the whole page** turns on the compatibility mode: the page is buffered and every script of a vendor is blocked. Turn it on only when it is needed, the buffer can conflict with the other plugins that use one.

## Your own scripts

To block a script of your theme, give it a category. See [Integrations](integrations.md#block-a-script).
