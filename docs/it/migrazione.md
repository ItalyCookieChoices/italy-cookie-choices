# Importare da un altro plugin

[Read in English](../migration.md).

Cookie Choices importa le impostazioni di un altro plugin per il consenso: il banner, i link, le categorie, i fornitori e i cookie. Le legge nel database, quindi l'altro plugin può essere attivo o disattivato, non eliminato.

## I plugin

* Complianz, la versione gratuita 7 e le versioni precedenti

Gli altri plugin arrivano nelle prossime versioni. Uno sviluppatore ne aggiunge uno con l'evento `BuildingMigrationSources`, vedi [Aggiungere un plugin](#aggiungere-un-plugin).

## Importare

Quando le impostazioni di un plugin sono nel database, la bacheca mostra un avviso. **Importa le impostazioni** apre la scheda **Strumenti**, sotto **Importa da un altro plugin**:

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

## Aggiungere un plugin

Una sorgente implementa `ItalyCookieChoices\Migration\Domain\MigrationSource`: un id, un nome, se le sue impostazioni sono nel database e le impostazioni nella forma dell'opzione di Cookie Choices, con i nomi delle classi `*Setting`.

```php
add_action(
    \ItalyCookieChoices\Migration\Application\BuildingMigrationSources::class,
    static function ( $event ): void {
        $event->addSource( new MyPluginSource() );
    }
);
```

Le impostazioni importate vengono salvate attraverso il sanitizer delle impostazioni, come quelle della pagina delle impostazioni.
