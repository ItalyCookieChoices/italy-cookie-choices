# Import from another plugin

[Leggi in italiano](it/migrazione.md).

Cookie Choices imports the settings of another consent plugin: the banner, the links, the categories, the vendors and the cookies. It reads them in the database, so the other plugin can be active or deactivated, not deleted.

## The plugins

* Complianz, the free version 7 and the versions before

The other plugins come in the next versions. A developer adds one with the `BuildingMigrationSources` event, see [Add a plugin](#add-a-plugin).

## Import

When the settings of a plugin are in the database, the dashboard shows a notice. **Import the settings** opens the **Tools** tab, under **Import from another plugin**:

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

## Add a plugin

A source implements `ItalyCookieChoices\Migration\Domain\MigrationSource`: an id, a name, whether its settings are in the database and the settings in the shape of the option of Cookie Choices, with the names of the `*Setting` classes.

```php
add_action(
    \ItalyCookieChoices\Migration\Application\BuildingMigrationSources::class,
    static function ( $event ): void {
        $event->addSource( new MyPluginSource() );
    }
);
```

The imported settings are saved through the sanitizer of the settings, like the ones of the settings page.
