# Multisite networks

[Leggi in italiano](it/multisito.md).

On a multisite network, every site has its own settings, texts and consent log. The network administrator can give the settings of the main site to every site, so the banner, the blocked vendors and the integrations are set once.

## Use the settings of the main site

1. Activate Cookie Choices on the network, in **Network Admin > Plugins**.
2. Open **Network Admin > Settings > Cookie Choices**.
3. Turn on **Use the settings of the main site on every site** and save.

The main site keeps its settings page as it is. On the other sites:

* the settings come from the main site: the banner and its layout, the style, the blocked vendors, the integrations and the consent log settings;
* each site keeps its own texts, for its language: the banner text, the policy link and its text, the text of the accept button, the label of the floating button, the placeholder texts and the names and descriptions of the categories;
* the settings page shows only the **Texts** tab, and the **Consent log** tab with the export and the statistics of the site;
* each site keeps its own consent log, in its own table.

The abilities that change the vendors return an error on these sites, the vendors come from the main site.

## Back to the settings of each site

Turn the switch off and save: each site uses again the settings stored on it, the ones it had before, or the last ones saved on its settings page.

## Copy the settings between sites

While every site has its own settings, the same page copies the settings of a site to other sites, without exporting a file and importing it on each site.

1. Open **Network Admin > Settings > Cookie Choices**.
2. In **Copy the settings**, choose the site in **Copy from** and the sites in **Copy to**.
3. Turn on **Copy the texts too** to copy the texts as well. Off, each site keeps its own texts, for its language.
4. Click **Copy the settings** and confirm.

The settings of the chosen sites are replaced. Each site always keeps:

* the logo of the banner, the pages where the banner does not open and the cookie domain, which belong to its own content and domain;
* its consent log, in its own table.

The copy happens once: the sites are not linked, a later change on the first site stays there. The copy is not available while the sites use the settings of the main site.
