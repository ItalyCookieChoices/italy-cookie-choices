# WP-CLI commands

[Leggi in italiano](it/wp-cli.md).

Cookie Choices adds the `wp cookie-choices` commands to [WP-CLI](https://wp-cli.org/), for the scripts, the deploys and the sites managed from the terminal. `wp help cookie-choices <command>` describes each one.

| Command | What it does |
| --- | --- |
| `wp cookie-choices status` | Shows the banner, the blocked vendors, the integrations and the consent log |
| `wp cookie-choices settings export` | Exports the settings as JSON |
| `wp cookie-choices settings import <file>` | Imports the settings of a JSON file |
| `wp cookie-choices log export` | Exports the consent log as CSV |
| `wp cookie-choices log purge` | Deletes the old records of the consent log |
| `wp cookie-choices scan` | Scans the pages of the site and lists the vendors found |
| `wp cookie-choices migrate` | Imports the settings of another consent plugin |

## status

```bash
wp cookie-choices status
wp cookie-choices status --format=json
```

The list has a row for each setting: `banner`, `blocked_vendors`, `consent_mode` (`off`, `basic` or `advanced`), `microsoft_uet`, `microsoft_clarity`, `global_privacy_control`, `consent_duration_days`, `cookie_domain`, `consent_log`, `consent_log_retention_months` and, on a network, `uses_main_site_settings`. `--format` takes `table`, `json`, `yaml` or `csv`.

## settings export and import

```bash
wp cookie-choices settings export --file=cookie-choices.json
wp cookie-choices settings import cookie-choices.json --yes
```

The file is the same of **Tools** in the settings page, so a file exported there is imported here and the other way round. Without `--file` the export goes to the standard output. The import also reads the files of the 2.x versions, it migrates the settings from the version of the file and replaces the current ones: it asks to confirm, `--yes` answers for you.

## log export and purge

```bash
wp cookie-choices log export --file=consent-log.csv
wp cookie-choices log purge
wp cookie-choices log purge --before=2026-01-01 --yes
wp cookie-choices log purge --all --yes
```

The CSV is the same of the **Consent log** tab, see [Consent log](consent-log.md). Without `--file` it goes to the standard output.

`log purge` deletes the records older than the retention of the settings, the plugin already does it every day. With `--before` it deletes the records before a date, `YYYY-MM-DD` in the timezone of the site, with `--all` all of them. The records demonstrate the consents: export the log before deleting it. It asks to confirm, `--yes` answers for you.

## scan

```bash
wp cookie-choices scan --home --post-type=page
wp cookie-choices scan --category=3,7 --post=42 --format=json
```

The same scan of the **Blocking** tab, see [Scan the site](blocking.md#scan-the-site): `--home` for the home page, `--post-type`, `--category` and `--post` for the post types, the ids of the categories and the ids of the posts, separated by commas. The list has a row for each vendor found, with its name, category, whether it is blocked (`on` or `off`) and the number of pages, then a row for each unknown third party, with only its host and its pages. The pages that cannot be read are reported as errors. It changes nothing.

## migrate

```bash
wp cookie-choices migrate
wp cookie-choices migrate complianz --dry-run
wp cookie-choices migrate complianz --yes
```

Without a plugin it lists the plugins whose settings are in the database, with something to import. With one, it shows what the import changes and what to check, then imports after the confirmation; `--dry-run` stops before and exits with code 0. See [Import from another plugin](migration.md).

## Exit codes

| Code | Meaning |
| --- | --- |
| 0 | Done |
| 1 | The command failed |
| 2 | Not confirmed, nothing changed |
| 3 | The file cannot be written |
| 4 | The file cannot be read |
| 5 | The file has no settings of Cookie Choices, or they are of a newer version of the plugin |
| 6 | The date is not valid |
| 7 | There are no published pages to scan in the selection |
| 8 | There are no settings of this plugin to import |
