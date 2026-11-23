# Abilities for the AI tools

[Leggi in italiano](it/abilita.md).

From WordPress 6.9, the plugins describe what they can do with the [Abilities API](https://developer.wordpress.org/apis/abilities/): each ability has a name, a description, the shape of its input and output, and who can run it. The AI assistants connected with the [MCP Adapter](https://github.com/WordPress/mcp-adapter) and the automation tools find the abilities and run them as the user who is logged in, without knowing the settings page.

Cookie Choices registers its abilities in the **Cookie Choices** category. Only the administrators can run them, the users with the `manage_options` capability, the same as for the settings page. On WordPress 6.5 to 6.8 nothing is registered.

## The abilities

| Name | What it does | Changes the site |
| --- | --- | --- |
| `italy-cookie-choices/get-status` | Returns the status of the dashboard widget: what makes the consent valid, `warning` for what is missing. | No |
| `italy-cookie-choices/get-settings` | Returns the saved settings, with the names of the export of the settings page. | No |
| `italy-cookie-choices/list-vendors` | Lists the common vendors and the custom vendors, their category and whether they are blocked. | No |
| `italy-cookie-choices/get-consent-statistics` | Counts the answers to the banner by month, from the [consent log](consent-log.md). | No |
| `italy-cookie-choices/scan-site` | Reads ten of the chosen pages and finds their vendors and unknown third parties, like **Scan the site**. | No |
| `italy-cookie-choices/get-scan-report` | Returns the report of the last scheduled scan. | No |
| `italy-cookie-choices/list-migration-sources` | Lists the other consent plugins to import, with what each import changes. | No |
| `italy-cookie-choices/update-settings` | Changes some settings, checked like on the settings page. | Yes |
| `italy-cookie-choices/switch-vendor` | Turns a common vendor on or off, like **Blocking > Common vendors**. | Yes |
| `italy-cookie-choices/add-custom-vendor` | Adds a custom vendor, like **Blocking > Custom vendors**. | Yes |
| `italy-cookie-choices/add-site-cookie` | Adds a cookie of the site to the [cookie table](cookie-table.md), like **Blocking > Site cookies**. | Yes |
| `italy-cookie-choices/import-settings` | Imports the settings of another consent plugin, like **Tools > Import from another plugin**. | Yes |

Each ability tells the AI tools whether it only reads, whether it can replace what is saved, and whether running it twice changes nothing more. To configure the plugin with an AI agent, see [Configure Cookie Choices with an AI agent](ai-agent.md).

### update-settings

The input has only the settings to change, with the names of `get-settings`. A checkbox is on with `"1"`, `null` removes a setting, like a checkbox turned off, and a list replaces the saved list. The settings are checked like on the settings page: a value that is not valid is not saved, and the ability returns the saved settings. On a site of a network that uses the settings of the main site, only the texts of the site change.

```json
{ "text": "We use cookies to measure the visits.", "consent_log": "1", "reload": null }
```

### scan-site

The input is the selection of **Scan the site**: `home`, `postTypes`, `categories` and `posts`, with the ids. Each run reads ten pages: run it again with `offset` set to the `nextOffset` it returns, until `nextOffset` is `null`.

```json
{ "home": true, "postTypes": ["page"], "offset": 0 }
```

### import-settings

The input is the id of a plugin from `list-migration-sources`, that shows what the import changes before it runs. The texts replace the saved ones, the vendors, the cookies and the scripts are added.

```json
{ "source": "complianz" }
```

### add-site-cookie

The input is the name of the cookie, its category, `necessary`, `preferences`, `statistics` or `marketing`, its duration and its purpose. The name must be new.

```json
{ "name": "wp_lang", "category": "necessary", "duration": "Session", "purpose": "Keeps the language chosen." }
```

### switch-vendor

The input is the id of a common vendor, from `list-vendors`, and `enabled`: `true` blocks its scripts and frames until the consent, `false` stops blocking them.

```json
{ "id": "youtube", "enabled": false }
```

### add-custom-vendor

The input is the name of the vendor, the category of consent it waits for, `preferences`, `statistics` or `marketing`, and the patterns: texts of the addresses of its scripts and frames, or of its inline scripts. The id is made from the name, like on the settings page, and must be new.

```json
{ "name": "Chat Widget", "category": "preferences", "patterns": ["chat.example.com"] }
```

It returns the vendor as it is saved. Add its cookies for the [cookie table](cookie-table.md) on the settings page.

## The REST API

The abilities are in the REST API of WordPress too, for the logged in administrators or with an [application password](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#application-passwords):

* `GET /wp-json/wp-abilities/v1/abilities?category=italy-cookie-choices` lists them.
* `GET /wp-json/wp-abilities/v1/abilities/italy-cookie-choices/list-vendors/run` runs an ability that only reads.
* `POST /wp-json/wp-abilities/v1/abilities/italy-cookie-choices/switch-vendor/run` with `{"input": {"id": "youtube", "enabled": false}}` runs an ability that changes the site.
