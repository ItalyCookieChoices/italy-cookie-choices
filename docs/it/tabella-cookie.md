# Tabella dei cookie

[Read in English](../cookie-table.md).

La cookie policy elenca i cookie del sito. Aggiungi il blocco **Tabella dei cookie**, o lo shortcode `[cookie_table]`, alla pagina della cookie policy: il plugin stampa una tabella per ogni categoria con dei cookie, con il nome, il fornitore, lo scopo e la durata di ogni cookie.

La tabella segue le impostazioni, cambia quando attivi o disattivi un fornitore.

## Cosa elenca

* Il cookie del consenso del plugin, `icc_consent`, nella categoria necessaria.
* I cookie dei fornitori comuni attivi in **Blocco > Fornitori comuni**, come li descrive la loro documentazione.
* I cookie dei fornitori personalizzati: aggiungili in **Blocco > Fornitori personalizzati**, con **Aggiungi un cookie**.
* I cookie del sito stesso, come l'accesso di WordPress o il carrello del negozio: aggiungili in **Blocco > Cookie del sito**, con la loro categoria.
* I cookie che gli altri plugin registrano con `wp_add_cookie_info()` della [WP Consent API](integrazioni.md#wp-consent-api), quando è attiva.

Un nome che finisce con `*` è un prefisso: `_ga_*` è il cookie `_ga_` seguito dall'id della proprietà.

## I cookie senza consenso vengono cancellati

A ogni pagina e dopo ogni scelta, il plugin cancella i cookie della tabella la cui categoria non ha il consenso: un rifiuto o una revoca rimuovono, per esempio, `_ga` e `_fbp` impostati prima dai fornitori. I cookie necessari non vengono mai cancellati.

Cancella i cookie sul dominio del sito e sui suoi domini superiori, dove i fornitori di solito li impostano, come `.example.com` per `www.example.com`. Il browser non permette a uno script di raggiungere i cookie HttpOnly e i cookie degli altri siti, come quelli di YouTube su `youtube.com`: restano fino alla scadenza, o finché il visitatore non li cancella.

## I cookie dei fornitori comuni

I nomi, le durate e gli scopi vengono dalla documentazione di ogni fornitore, dai suoi script quando la documentazione non riporta le durate, o da cookiedatabase.org. I fornitori possono cambiarli: verifica i cookie del tuo sito con gli strumenti per sviluppatori del browser.

Google Tag Manager non imposta cookie propri. Gli embed di Instagram, X e SoundCloud impostano i loro cookie sui propri domini e non li documentano, aggiungili come cookie del sito se ti servono.

## Per gli sviluppatori

Aggiungi o rimuovi un cookie con `BuildingCookieTable`, per esempio i cookie di un tema:

```php
use ItalyCookieChoices\Cookies\Application\BuildingCookieTable;
use ItalyCookieChoices\Cookies\Domain\Cookie;

add_action( BuildingCookieTable::class, static function ( BuildingCookieTable $event ): void {
	$event->addCookie( new Cookie( 'theme_dark', 'Il mio tema', 'preferences', 'Ricorda la modalità scura.', '1 anno' ) );
	$event->removeCookie( 'test_cookie' );
} );
```

Un cookie viene elencato una volta: vince il primo con quel nome.
