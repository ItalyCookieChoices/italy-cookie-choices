# Consent log

The GDPR asks the site to demonstrate the consents it relies on (art. 7(1)). With **Consent log > Log the consents** the plugin stores every choice of the visitors.

## What is stored

Each record has:

- a random id created by the browser, the same for the next choices of that browser;
- the accepted categories, none for a rejection or a withdrawal;
- the version of the cookie policy;
- the date, in UTC.

No IP address, no user agent and no account: the log does not identify the visitor. The id is stored in the consent cookie too, so a visitor can tell you their id to find their choices.

The records are sent by the browser to `/wp-json/italy-cookie-choices/v1/consents`, so the log works with the cached pages.

## Retention

**Keep the records for** deletes the records older than 3, 6, 12, 24 or 36 months, 12 by default. The cleanup runs once a day.

## Export

**Export the log as CSV** downloads every record, with the `consent_id`, `recorded_at`, `policy_version`, `categories` and `withdrawn` columns. Only the administrators can export it.

## Privacy policy

The plugin suggests a text for your privacy policy in **Settings > Privacy > Policy guide**, with the categories, the consent cookie and the log.

## Uninstall

Deleting the plugin deletes the log with its table, and the settings.
