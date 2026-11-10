# Multilingual sites

The texts of the plugin, like the buttons and the default category names, are translated in the language of the site. The plugin includes the Italian translation, the other languages come from [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/italy-cookie-choices/).

The texts you write in the settings are translated with your multilingual plugin.

## WPML

Open **WPML > String Translation** and choose the **Italy Cookie Choices** domain, the name of the 2.x versions kept for the saved translations. The strings are registered every time an administrator opens the dashboard:

* Banner text
* Banner url
* Banner anchor text
* Banner button text
* Content message text
* Content message button text
* Floating button label
* Category *id* label and Category *id* description, for the category texts you changed

## Polylang

Open **Languages > Translations** and filter the **Italy Cookie Choices** group. The strings are the same of WPML.

## TranslatePress

TranslatePress translates the whole page, the banner and the preferences too: no setting is needed. The texts you write in the settings and the policy link are in the translation editor with the other texts of the page, and the policy link points to the page in the language of the visitor. The buttons and the category names come from the translation of the plugin in each language, like the other texts of WordPress.

The banner shows only before a choice: to translate it in the visual editor, open the editor in a private window of the browser, or delete the `icc_consent` cookie.

## Weglot

Weglot translates the whole page too, the banner and the preferences included: its automatic translation adds their texts to the list of the translations of the Weglot dashboard, where you can change them. No setting of the plugin is needed.

## Other plugins

Other plugins, like qTranslate-XT, translate the texts with the `TranslatingText` event. See [Integrations](integrations.md#translate-the-texts-with-another-multilingual-plugin).
