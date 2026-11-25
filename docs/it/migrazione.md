# Importare da un altro plugin

[Read in English](../migration.md).

Cookie Choices importa le impostazioni di un altro plugin per il consenso: il banner, i link, le categorie, i fornitori e i cookie. Le legge nel database, quindi l'altro plugin può essere attivo o disattivato, non eliminato.

## I plugin

* Complianz, la versione gratuita 7 e le versioni precedenti
* CookieYes, la versione 3 e le versioni 2, chiamate GDPR Cookie Consent
* Cookie Notice & Compliance, il banner del plugin e quello che l'app Compliance tiene sul sito
* GDPR Cookie Compliance, di Moove Agency, la versione gratuita
* Borlabs Cookie, la versione 3
* Real Cookie Banner, la versione gratuita 5
* CookieAdmin, la versione gratuita
* WPConsent, la versione gratuita

Gli altri plugin arrivano nelle prossime versioni. Uno sviluppatore ne aggiunge uno con l'evento `BuildingMigrationSources`, vedi [Aggiungere un plugin](#aggiungere-un-plugin).

## Importare

Quando le impostazioni di un plugin sono nel database e c'è qualcosa da importare, la bacheca mostra un avviso. **Importa le impostazioni** apre la scheda **Strumenti**, sotto **Importa da un altro plugin**:

1. **Anteprima** elenca cosa cambia l'importazione, e cosa controllare dopo. Non viene salvato nulla.
2. **Importa** salva le impostazioni.
3. Disattiva l'altro plugin, o il sito mostra due banner. Le sue impostazioni restano, l'importazione si può ripetere.

**Ignora** nasconde l'avviso. Dal terminale, vedi [WP-CLI](wp-cli.md#migrate).

## Cosa cambia

* Le impostazioni che l'altro plugin ha sostituiscono quelle di Cookie Choices, le altre restano.
* Gli elenchi ricevono le nuove voci e tengono le loro: i fornitori personalizzati, gli script dopo il consenso, gli indirizzi mai bloccati e i cookie del sito.
* Le categorie tengono i testi che l'altro plugin non ha.
* I fornitori comuni che l'altro plugin usa vengono attivati, gli altri restano come sono.
* Una funzione che l'altro plugin lascia spenta tiene il predefinito di Cookie Choices: l'importazione accende le funzioni, non le spegne.
* Ai visitatori viene chiesto di nuovo il consenso: il consenso dato nell'altro banner non viene riportato.

## Da Complianz

| Complianz | Cookie Choices |
| --- | --- |
| I testi e i pulsanti del banner predefinito | Il testo del banner e i testi dei pulsanti |
| La posizione, il logo e i colori del banner | La scheda **Stile** |
| Il CSS personalizzato | Il CSS personalizzato, in un commento: i suoi selettori sono quelli di Complianz |
| Le categorie | I testi delle categorie, Funzionali diventa Necessari |
| La cookie policy e l'Impressum | I link alla policy e alle note legali |
| La scadenza del consenso | La durata del consenso più vicina |
| Il dominio del cookie, le pagine senza banner, Do Not Track e Global Privacy Control | Le stesse impostazioni |
| Il pulsante Gestisci consenso | Il pulsante flottante |
| Il consenso per servizio | Il consenso per fornitore |
| Google Consent Mode, gli id di Google Analytics e Tag Manager | La scheda **Integrazioni** |
| I servizi e i social network | I fornitori comuni con lo stesso nome |
| Gli script bloccati dello script center | Fornitori personalizzati |
| Gli script aggiunti dello script center | Gli script dopo il consenso |
| Gli script in whitelist | Gli indirizzi mai bloccati |
| I cookie del sito | I cookie del sito |
| I cookie di uno script bloccato | I cookie del suo fornitore personalizzato |

Non vengono importati: il consenso allo scorrimento o dopo un tempo, non è un consenso valido; le regioni e il banner per gli Stati Uniti; TCF; i documenti che Complianz genera, il link alla pagina viene mantenuto; le traduzioni. L'anteprima elenca quelli che il sito usa.

## Da CookieYes

| CookieYes | Cookie Choices |
| --- | --- |
| I testi e i pulsanti del banner attivo, nella lingua predefinita | Il testo del banner e i testi dei pulsanti |
| Il link alla privacy e il suo pulsante Leggi di più | Il link alla policy, la nuova scheda e il nofollow |
| Il tipo, la posizione, il logo e i colori del banner | La scheda **Stile**, il popup diventa la finestra modale |
| Il CSS personalizzato | Il CSS personalizzato, in un commento: i suoi selettori sono quelli di CookieYes |
| Le categorie | I testi delle categorie: Funzionali diventa Preferenze, Analitici e Prestazioni diventano Statistiche, Pubblicità diventa Marketing |
| La scadenza del consenso, il ricaricamento dopo l'accettazione e Global Privacy Control | Le stesse impostazioni |
| Il pulsante per rivedere il consenso | Il pulsante flottante |
| Il titolo del segnaposto dei video | Il testo segnaposto dei contenuti bloccati |
| Google Consent Mode | La scheda **Integrazioni** |
| I cookie con il pattern dello script di un fornitore comune | Quel fornitore comune, attivato |
| I cookie con un altro pattern dello script | Un fornitore personalizzato per ogni pattern, con i suoi cookie |
| I cookie senza pattern dello script | I cookie del sito |

Le versioni 2 importano i testi, il link Leggi di più, la posizione, i colori, il pulsante per rivedere il consenso e i cookie, come cookie del sito.

Non vengono importati: il banner per gli Stati Uniti con l'opt-out, il geo-targeting, il consenso allo scorrimento, le traduzioni. Quando il sito usa la web app di CookieYes, le sue impostazioni in WordPress possono essere più vecchie di quelle della web app: controllale dopo l'importazione.

## Da Cookie Notice & Compliance

| Cookie Notice | Cookie Choices |
| --- | --- |
| Il messaggio, i testi dei pulsanti di accettazione e di rifiuto | Il testo del banner e i testi dei pulsanti |
| Il link alla privacy policy, una pagina o un indirizzo, e la sua destinazione | Il link alla policy e la nuova scheda |
| La posizione e i colori della barra | La scheda **Stile** |
| La scadenza del cookie | La durata del consenso più vicina |
| Il ricaricamento dopo l'accettazione | Ricarica la pagina dopo la scelta |
| Il pulsante di revoca | Il pulsante flottante |
| Il codice dello script blocking, nell'head e nel body | Gli script dopo il consenso, nella categoria marketing |

Cookie Notice chiede un solo consenso per tutto: i suoi script attendono il consenso marketing, cambia la loro categoria in **Integrazioni > Script dopo il consenso** quando sono statistiche. Con l'app Compliance, il banner, i suoi testi e le sue categorie sono nell'app: l'importazione porta la posizione, il colore, il link alla policy, Global Privacy Control, Google Consent Mode e i provider personalizzati, come fornitori personalizzati.

Non vengono importati: il consenso allo scorrimento o al clic, le regole di visualizzazione condizionale, le traduzioni.

## Da GDPR Cookie Compliance

| GDPR Cookie Compliance | Cookie Choices |
| --- | --- |
| Il testo del banner e le etichette dei suoi pulsanti, il pulsante di salvataggio delle impostazioni | Il testo del banner e i testi dei pulsanti |
| La posizione del banner, lo schema di colori e il logo dell'azienda | La scheda **Stile** |
| La scadenza del consenso | La durata del consenso più vicina |
| Il ricaricamento forzato | Ricarica la pagina dopo la scelta |
| Il pulsante flottante e la sua etichetta | Il pulsante flottante, a sinistra |
| Le schede: cookie strettamente necessari, di terze parti e aggiuntivi | I testi delle categorie Necessari, Statistiche e Marketing |
| Gli script di ogni scheda, nell'head, nel body e nel footer | Gli script dopo il consenso, nella categoria della scheda |
| Gli id di Google Analytics 4, Tag Manager e del pixel di Facebook delle integrazioni | La scheda **Integrazioni** |
| Google Tag Manager con Consent Mode | Google Consent Mode |
| Google Ads | Il fornitore comune Google Ads, il suo tag non viene importato |

I testi vengono letti nella lingua predefinita di WPML o Polylang. Non vengono importati: il tag di Google Ads, le traduzioni e le funzioni premium.

## Da Borlabs Cookie

| Borlabs Cookie | Cookie Choices |
| --- | --- |
| I testi della finestra e dei suoi pulsanti | Il testo del banner e i testi dei pulsanti |
| I link alla privacy e all'impressum, una pagina o un indirizzo | I link alla policy e alle note legali |
| Le pagine dove la finestra è nascosta | Le pagine dove il banner non si apre da solo |
| Il layout e la posizione, il logo, i colori e il CSS personalizzato della finestra | La scheda **Stile**, il CSS personalizzato in un commento |
| La durata del cookie, il dominio del cookie, il ricaricamento dopo l'opt-in | Le stesse impostazioni |
| Il widget | Il pulsante flottante |
| Gli host esclusi dai content blocker | Gli indirizzi mai bloccati |
| I gruppi di servizi | I testi delle categorie: Essenziali diventa Necessari, Media esterni diventa Marketing |
| I servizi di un fornitore comune, come Google Analytics o YouTube | Quel fornitore comune, attivato, con gli id dei tag di Google e Facebook e Consent Mode |
| Gli altri servizi con una posizione | Fornitori personalizzati con i loro cookie, il loro codice di opt-in come script dopo il consenso |
| I cookie dei servizi essenziali | I cookie del sito |

I testi vengono letti nella lingua del sito. Non vengono importati: i servizi senza posizione il cui codice richiede Borlabs Cookie, il codice di opt-out, il geo-targeting, IAB TCF, le traduzioni. Borlabs Cookie è un plugin a pagamento: l'importazione legge le sue impostazioni nel database, non usa il suo codice.

## Da Real Cookie Banner

| Real Cookie Banner | Cookie Choices |
| --- | --- |
| La descrizione del banner e i testi dei suoi pulsanti | Il testo del banner e i testi dei pulsanti |
| I link legali alla privacy policy e alle note legali, una pagina o un indirizzo | I link alla policy e alle note legali |
| Le pagine dei link legali che nascondono il banner | Le pagine dove il banner non si apre da solo |
| Il layout e la posizione, il logo, i colori e il CSS personalizzato | La scheda **Stile**, il CSS personalizzato in un commento |
| La durata del cookie del consenso | La durata del consenso |
| I link fissi | Il pulsante flottante |
| I gruppi di servizi | I testi delle categorie: Funzionali diventa Preferenze, i gruppi personalizzati Marketing |
| I servizi di un fornitore comune, trovati dal loro modello, codice o dalle regole dei blocchi | Quel fornitore comune, attivato, con gli id dei tag di Google e Facebook; il codice degli altri fornitori come script dopo il consenso |
| Gli altri servizi | Fornitori personalizzati con le regole degli indirizzi dei loro content blocker e i loro cookie, il loro codice di opt-in come script dopo il consenso |
| I cookie dei servizi essenziali | I cookie del sito |

I testi vengono letti nella lingua del sito, anche con WPML o Polylang. Vengono importati solo i servizi e i blocchi pubblicati, i campi del codice, come `{{measurementId}}`, vengono compilati. Non vengono importati: i servizi senza regole dei blocchi il cui codice richiede Real Cookie Banner, i selettori HTML dei blocchi, il codice di opt-out, il titolo, i testi dei content blocker, IAB TCF, l'inoltro del consenso e il bypass per paese.

## Da CookieAdmin

| CookieAdmin | Cookie Choices |
| --- | --- |
| L'avviso e i testi dei suoi pulsanti, del banner GDPR | Il testo del banner e i testi dei pulsanti |
| Il link alla privacy policy, una pagina o un indirizzo | Il link alla policy |
| Il layout e la posizione, i colori dello sfondo e del testo | La scheda **Stile**: il box sul suo lato, il footer in alto o in basso, il pop-up al centro |
| I giorni del consenso | La durata del consenso |
| Google Consent Mode, la modalità avanzata, il consenso di Clarity, il Global Privacy Control | Le stesse impostazioni |
| Gli embed di YouTube, Vimeo, SoundCloud, Dailymotion e Google Maps che blocca | Quei fornitori comuni, attivati |
| I cookie delle sue scansioni di un fornitore comune, come `_ga` | Quel fornitore comune, attivato |
| Gli altri cookie delle sue scansioni, con la loro categoria | I cookie del sito, con la durata e la descrizione |

I testi lasciati ai valori predefiniti di CookieAdmin non vengono salvati da lui, quindi restano quelli di Cookie Choices. Non vengono importati: i titoli, i testi delle preferenze, le leggi degli Stati Uniti, l'icona personalizzata e i cookie senza categoria.

## Da WPConsent

| WPConsent | Cookie Choices |
| --- | --- |
| Il banner attivo o no, il suo messaggio e i testi dei suoi pulsanti | Il banner, il suo testo e i testi dei pulsanti |
| La pagina della cookie policy | Il link alla policy |
| Il layout e la posizione, il logo, i colori dello sfondo e del testo | La scheda **Stile**: il banner lungo in alto o in basso, quello flottante sul suo lato, il modale al centro |
| La durata del consenso | La stessa impostazione |
| Google Consent Mode, il Global Privacy Control | Le stesse impostazioni |
| Le categorie | I testi delle categorie: Essenziali diventa Necessari |
| I servizi di un fornitore comune, come Google Analytics o Facebook Pixel | Quel fornitore comune, attivato |
| I cookie della categoria essenziale e degli altri servizi | I cookie del sito, con la durata e la descrizione |

WPConsent blocca gli altri servizi con un suo elenco, non con indirizzi: aggiungi gli indirizzi dei loro script come fornitori personalizzati, il resoconto dell'importazione li elenca. Non vengono importati: i testi delle preferenze, la geolocalizzazione, IAB TCF e i documenti legali.

## Aggiungere un plugin

Una sorgente implementa `ItalyCookieChoices\Migration\Domain\MigrationSource`: un id, un nome, se le sue impostazioni sono nel database e le impostazioni nella forma dell'opzione di Cookie Choices, con i nomi delle classi `*Setting`. Una sorgente che non legge impostazioni né fornitori non viene elencata.

```php
add_action(
    \ItalyCookieChoices\Migration\Application\BuildingMigrationSources::class,
    static function ( $event ): void {
        $event->addSource( new MyPluginSource() );
    }
);
```

Le impostazioni importate vengono salvate attraverso il sanitizer delle impostazioni, come quelle della pagina delle impostazioni.
