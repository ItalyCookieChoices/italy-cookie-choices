# Comandi WP-CLI

[Read in English](../wp-cli.md).

Cookie Choices aggiunge i comandi `wp cookie-choices` a [WP-CLI](https://wp-cli.org/), per gli script, i deploy e i siti gestiti dal terminale. `wp help cookie-choices <comando>` descrive ognuno.

| Comando | Cosa fa |
| --- | --- |
| `wp cookie-choices status` | Mostra il banner, i fornitori bloccati, le integrazioni e il registro dei consensi |
| `wp cookie-choices settings export` | Esporta le impostazioni in JSON |
| `wp cookie-choices settings import <file>` | Importa le impostazioni di un file JSON |
| `wp cookie-choices log export` | Esporta il registro dei consensi in CSV |
| `wp cookie-choices log purge` | Cancella i record vecchi del registro dei consensi |

## status

```bash
wp cookie-choices status
wp cookie-choices status --format=json
```

L'elenco ha una riga per ogni impostazione: `banner`, `blocked_vendors`, `consent_mode` (`off`, `basic` o `advanced`), `microsoft_uet`, `microsoft_clarity`, `global_privacy_control`, `consent_duration_days`, `cookie_domain`, `consent_log`, `consent_log_retention_months` e, in una rete, `uses_main_site_settings`. `--format` accetta `table`, `json`, `yaml` o `csv`.

## settings export e import

```bash
wp cookie-choices settings export --file=cookie-choices.json
wp cookie-choices settings import cookie-choices.json --yes
```

Il file è lo stesso di **Strumenti** nella pagina delle impostazioni, quindi un file esportato lì si importa qui e viceversa. Senza `--file` l'esportazione va sullo standard output. L'importazione legge anche i file delle versioni 2.x, migra le impostazioni dalla versione del file e sostituisce quelle attuali: chiede di confermare, `--yes` risponde per te.

## log export e purge

```bash
wp cookie-choices log export --file=consent-log.csv
wp cookie-choices log purge
wp cookie-choices log purge --before=2026-01-01 --yes
wp cookie-choices log purge --all --yes
```

Il CSV è lo stesso della scheda **Registro dei consensi**, vedi [Registro dei consensi](registro-consensi.md). Senza `--file` va sullo standard output.

`log purge` cancella i record più vecchi della conservazione delle impostazioni, il plugin lo fa già ogni giorno. Con `--before` cancella i record prima di una data, `AAAA-MM-GG` nel fuso orario del sito, con `--all` tutti. I record dimostrano i consensi: esporta il registro prima di cancellarlo. Chiede di confermare, `--yes` risponde per te.

## Codici di uscita

| Codice | Significato |
| --- | --- |
| 0 | Fatto |
| 1 | Il comando non è riuscito |
| 2 | Non confermato, nulla è cambiato |
| 3 | Il file non può essere scritto |
| 4 | Il file non può essere letto |
| 5 | Il file non ha impostazioni di Cookie Choices, o sono di una versione più recente del plugin |
| 6 | La data non è valida |
