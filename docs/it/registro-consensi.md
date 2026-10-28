# Registro dei consensi

Il GDPR chiede al sito di dimostrare i consensi su cui si basa (art. 7, paragrafo 1). Con **Registro dei consensi > Registra i consensi** il plugin salva ogni scelta dei visitatori.

## Cosa viene salvato

Ogni record contiene:

- un id casuale creato dal browser, lo stesso per le scelte successive di quel browser;
- le categorie accettate, nessuna per un rifiuto o una revoca;
- la versione della cookie policy;
- la data, in UTC.

Nessun indirizzo IP, nessun user agent e nessun account: il registro non identifica il visitatore. L'id è salvato anche nel cookie del consenso, così un visitatore può comunicarti il suo id per trovare le sue scelte.

I record vengono inviati dal browser a `/wp-json/italy-cookie-choices/v1/consents`, quindi il registro funziona con le pagine in cache.

## Conservazione

**Conserva i record per** elimina i record più vecchi di 3, 6, 12, 24 o 36 mesi, 12 per impostazione predefinita. La pulizia viene eseguita una volta al giorno.

## Esportazione

**Esporta il registro in CSV** scarica tutti i record, con le colonne `consent_id`, `recorded_at`, `policy_version`, `categories` e `withdrawn`. Solo gli amministratori possono esportarlo.

## Privacy policy

Il plugin suggerisce un testo per la tua privacy policy in **Impostazioni > Privacy > Guida alla policy**, con le categorie, il cookie del consenso e il registro.

## Disinstallazione

Eliminare il plugin elimina il registro con la sua tabella, e le impostazioni.
