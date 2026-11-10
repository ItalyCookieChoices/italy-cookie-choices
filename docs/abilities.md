# Abilities for the AI tools

[Leggi in italiano](it/abilita.md).

From WordPress 6.9, the plugins describe what they can do with the [Abilities API](https://developer.wordpress.org/apis/abilities/): each ability has a name, a description, the shape of its input and output, and who can run it. The AI assistants connected with the [MCP Adapter](https://github.com/WordPress/mcp-adapter) and the automation tools find the abilities and run them as the user who is logged in, without knowing the settings page.

Cookie Choices registers its abilities in the **Cookie Choices** category. Only the administrators can run them, the users with the `manage_options` capability, the same as for the settings page. On WordPress 6.5 to 6.8 nothing is registered.

## The abilities

| Name | What it does | Changes the site |
| --- | --- | --- |
| `italy-cookie-choices/get-settings` | Returns the saved settings, with the names of the export of the settings page. | No |
| `italy-cookie-choices/list-vendors` | Lists the common vendors and the custom vendors, their category and whether they are blocked. | No |
| `italy-cookie-choices/get-consent-statistics` | Counts the answers to the banner by month, from the [consent log](consent-log.md). | No |
| `italy-cookie-choices/switch-vendor` | Turns a common vendor on or off, like **Blocking > Common vendors**. | Yes |
| `italy-cookie-choices/add-custom-vendor` | Adds a custom vendor, like **Blocking > Custom vendors**. | Yes |

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
