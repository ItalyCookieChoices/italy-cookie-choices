# Aggiornamento dalla 2.x

La versione 3.0 riscrive il plugin per rispettare le regole attuali sul consenso: la Direttiva ePrivacy, l'art. 122 del Codice Privacy con le linee guida del Garante del 2021 e i requisiti del consenso del GDPR.

Fai un backup del sito e prova l'aggiornamento su un sito di staging.

## Nome e menu

Il plugin ora si chiama **Cookie Choices**, funziona in tutti i paesi dell'UE. La sua pagina delle impostazioni passa da **Impostazioni** al suo menu **Cookie Choices**, il vecchio indirizzo apre la nuova pagina.

## Requisiti

* WordPress 6.5 o successivo, prima era 6.1.
* PHP 8.2 o successivo, prima era 7.4.

## Le tue impostazioni

Le impostazioni vengono migrate alla prima richiesta dopo l'aggiornamento:

* I testi, la posizione, il modello, i colori, il CSS e le classi personalizzate, il link alla policy e le pagine escluse vengono mantenuti.
* Le liste di blocco e gli snippet delle textarea della 2.x diventano un fornitore personalizzato chiamato **Custom**, nella categoria marketing. Cambia la sua categoria in **Blocco** se i suoi script sono statistiche.
* Le liste di script consentiti diventano **Mai bloccati**.
* Uno snippet della 2.x con il carattere jolly `<---------SOMETHING--------->` dentro il tag non può essere migrato com'era: viene mantenuto solo il suo pezzo di codice più lungo. Controlla il fornitore personalizzato dopo l'aggiornamento.
* Il **Registro dei consensi** viene attivato con una conservazione di 12 mesi.
* I segnali del **Google Consent Mode v2** vengono attivati.
* Il testo del pulsante della 2.x che chiudeva il banner, come "Chiudi", non viene mantenuto: ora il pulsante accetta tutte le categorie e mostra "Accetta tutti" nella lingua del sito. Scrivi un nuovo testo in **Banner > Testo del pulsante di accettazione** se ti serve.

I file delle impostazioni della 2.x si possono ancora importare da **Strumenti**.

## I visitatori danno di nuovo il consenso

Il consenso ora è salvato in un nuovo cookie, `icc_consent`, con le categorie accettate. I visitatori che hanno accettato con una versione 2.x vedono di nuovo il banner, perché il loro consenso non aveva categorie.

Il cookie della 2.x, `displayCookieConsent` per impostazione predefinita, viene ancora scritto quando tutte le categorie sono accettate.

## Cosa cambia per i visitatori

* Il banner ha tre pulsanti con la stessa importanza: accetta tutti, rifiuta tutti e preferenze. Chiuderlo mantiene solo i cookie necessari.
* Il consenso si dà per categoria: necessari, preferenze, statistiche e marketing.
* Il consenso con lo scroll e alla seconda pagina visitata sono stati rimossi, non sono un consenso valido. Riscrivi un testo del banner che dice che il visitatore acconsente scorrendo la pagina o proseguendo la navigazione.
* La scelta dura sei mesi, prima era un anno.
* I visitatori possono modificare o revocare il consenso dalle preferenze, aperte dal blocco **Pulsante delle preferenze cookie**, dallo shortcode `[cookie_preferences]` o da un link a `#icc-preferences`. Lo shortcode `[delete_cookie]` stampa lo stesso pulsante.

## Cosa cambia per il blocco

* I fornitori comuni sono sempre bloccati nel contenuto, nei widget e negli embed, le opzioni della 2.x per attivare il blocco sono state rimosse.
* La modalità di compatibilità, **Blocca i fornitori in tutta la pagina**, blocca solo gli script dei fornitori. La versione 2.x bloccava tutti gli script della pagina.
* I contenuti bloccati mostrano un segnaposto con un pulsante che accetta la loro categoria.

## Markup e stili

Il markup del banner è nuovo, usa le classi `icc-banner` e `icc-preferences`. Un CSS personalizzato scritto per il markup della 2.x, come `#cookieChoiceInfo`, va aggiornato. Le classi personalizzate delle impostazioni vengono ancora aggiunte.

## Siti multilingua

I testi vengono registrati in WPML e Polylang ogni volta che un amministratore apre la bacheca, Polylang ora li traduce. Ceceppa Multilingua non è più supportato.

## Per gli sviluppatori

* I filtri `icc_js_array` e `icc_output_html` sono stati rimossi, usa l'evento `BuildingVendors` per cambiare cosa viene bloccato. Vedi [Integrazioni](integrazioni.md).
* La dipendenza `overclokk/cookie` è stata rimossa.
* L'azione `icc_plugin_loaded` viene ancora eseguita, dopo il nuovo evento `PluginLoaded`.
* Il filtro `icc_bots` è deprecato, i bot non vengono più rilevati. Lo shortcode `[accept_button]` viene stampato per tutti i visitatori.
* Il consenso si legge nel browser con `window.italyCookieChoices`, o in PHP con il filtro `italy_cookie_choices_has_consent`.
* Un nome o un valore del cookie vuoto nelle impostazioni viene rifiutato con un errore, le versioni 2.x ripristinavano il valore predefinito.
