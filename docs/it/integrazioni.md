# Integrazioni

Come collegare uno script, un servizio o un tema al consenso di Cookie Choices.

Il consenso è salvato nel browser, quindi le pagine possono essere in cache: ogni integrazione lo legge in JavaScript, o lascia che il plugin blocchi i suoi script finché il visitatore non accetta la loro categoria.

Le categorie sono `necessary`, `preferences`, `statistics` e `marketing`. La categoria necessaria è sempre accettata.

## Google Consent Mode v2

Con **Integrazioni > Invia i segnali del Consent Mode** attivo, il plugin stampa uno script all'inizio del `<head>`, prima dei tag di Google:

```js
gtag( 'consent', 'default', {
	security_storage: 'granted',
	functionality_storage: 'denied',
	personalization_storage: 'denied',
	analytics_storage: 'denied',
	ad_storage: 'denied',
	ad_user_data: 'denied',
	ad_personalization: 'denied',
	wait_for_update: 500,
} );
gtag( 'set', 'ads_data_redaction', true );
```

Poi invia `gtag( 'consent', 'update', { ... } )` con il consenso salvato, e di nuovo a ogni scelta del visitatore.

Ogni categoria concede questi tipi di consenso:

| Categoria | Tipi di consenso |
| --- | --- |
| `necessary` | `security_storage` |
| `preferences` | `functionality_storage`, `personalization_storage` |
| `statistics` | `analytics_storage` |
| `marketing` | `ad_storage`, `ad_user_data`, `ad_personalization` |

### Modalità base e avanzata

Nella **modalità base**, quella predefinita, Google Analytics e Google Tag Manager sono bloccati fino al consenso alla loro categoria, poi vengono caricati con il consenso già concesso.

Nella **modalità avanzata** vengono caricati prima del consenso e inviano ping senza cookie finché non viene concesso. Verifica che sia consentito dove si trovano i tuoi visitatori: il Garante italiano richiede il consenso prima di qualsiasi tracciamento.

### Associare una categoria personalizzata

```php
use ItalyCookieChoices\Integrations\Application\BuildingConsentMode;
use ItalyCookieChoices\Integrations\Domain\GoogleConsentType;

add_action( BuildingConsentMode::class, static function ( BuildingConsentMode $event ): void {
	$event->mapCategory( 'social', GoogleConsentType::AD_STORAGE, GoogleConsentType::AD_USER_DATA );
} );
```

## Segnali del consenso di Microsoft

Con **Integrazioni > Invia il consenso a Microsoft Advertising (UET)** e **Invia il consenso a Microsoft Clarity** attivi, come da impostazione predefinita, il plugin stampa uno script all'inizio del `<head>`, prima dei tag di Microsoft. Le chiamate aspettano nelle code `window.uetq` e `window.clarity` finché i tag non vengono caricati.

Microsoft Advertising riceve l'archiviazione pubblicitaria negata come impostazione predefinita, poi il consenso salvato e ogni scelta:

```js
window.uetq.push( 'consent', 'default', { ad_storage: 'denied' } );
window.uetq.push( 'consent', 'update', { ad_storage: 'granted' } );
```

Microsoft Clarity riceve il consenso salvato, negato se non c'è, poi ogni scelta, con la sua Consent API v2. Clarity lo richiede per i visitatori dello SEE, del Regno Unito e della Svizzera:

```js
window.clarity( 'consentv2', { analytics_Storage: 'granted', ad_Storage: 'denied' } );
```

| Categoria | UET | Clarity |
| --- | --- | --- |
| `statistics` | | `analytics_Storage` |
| `marketing` | `ad_storage` | `ad_Storage` |

## Amazon Consent Signal

Da giugno 2026 Amazon Ads usa i dati dei visitatori dello SEE e del Regno Unito solo con un segnale del consenso. Con **Integrazioni > Invia il consenso ad Amazon Ads** attivo, il plugin stampa all'inizio del `<head>` lo script del consenso di Amazon, `amzn-consent.js`, e la sua chiamata `amznConsent()`, bloccati fino al consenso al marketing. Dopo il consenso partono prima dell'Amazon Ad Tag, `amzn.js`, con l'archiviazione pubblicitaria e i dati dell'utente concessi. Senza consenso non si carica nulla da Amazon: il preset **Amazon Ads** blocca anche il tag.

Amazon chiede il paese del visitatore. Il plugin non localizza i visitatori: scrivi le due lettere del paese dei tuoi visitatori in **Paese dei visitatori, per Amazon**, come `IT`, oppure lascialo vuoto per il paese della lingua del sito. Attiva il segnale solo quando il sito usa l'Amazon Ad Tag, lo script del consenso è di Amazon. Quando il banner usa l'IAB TCF, Amazon legge invece il segnale TCF.

## WP Consent API

Quando il plugin [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) è attivo e il banner è acceso, Cookie Choices gli scrive il consenso, così i plugin che lo leggono, come WooCommerce e Site Kit, rispettano la scelta del visitatore. Non c'è nessuna impostazione.

Senza un plugin del consenso l'API permette ogni categoria. Cookie Choices dichiara il tipo di consenso `optin`, quindi prima della scelta è permessa solo la categoria funzionale.

Ogni categoria permette queste categorie dell'API:

| Categoria | Categorie della WP Consent API |
| --- | --- |
| `necessary` | `functional` |
| `preferences` | `preferences` |
| `statistics` | `statistics`, `statistics-anonymous` |
| `marketing` | `marketing` |

Nel browser il plugin chiama `wp_set_consent()` con il consenso salvato e a ogni scelta, compresi il rifiuto e la revoca. In PHP `wp_has_consent()` risponde dal cookie del consenso di Cookie Choices, quindi un consenso scaduto non è mai permesso. I cookie dell'API durano quanto il consenso.

### Associare una categoria personalizzata

```php
use ItalyCookieChoices\Integrations\Application\BuildingWpConsentApi;
use ItalyCookieChoices\Integrations\Domain\WpConsentCategory;

add_action( BuildingWpConsentApi::class, static function ( BuildingWpConsentApi $event ): void {
	$event->mapCategory( 'social', WpConsentCategory::MARKETING );
} );
```

## I tag stampati dal plugin

La scheda **Integrazioni** stampa Google Analytics 4, Google Tag Manager e il pixel di Facebook dai loro id, ognuno bloccato fino al consenso alla sua categoria:

| Tag | Categoria |
| --- | --- |
| Google Analytics | `statistics` |
| Google Tag Manager | `marketing` |
| Pixel di Facebook | `marketing` |

Lascia un campo vuoto quando il tag è stampato dal tema o da un altro plugin. Il plugin blocca anche quei tag, quando corrispondono a un fornitore della scheda **Blocco**.

I video di YouTube usano `youtube-nocookie.com` con **Usa la modalità privacy avanzata per i video di YouTube**, e attendono comunque il consenso marketing.

## Script dopo il consenso

**Script dopo il consenso**, nella scheda **Integrazioni**, aggiunge codice alle pagine dopo il consenso alla sua categoria: una chat, uno snippet di tracciamento che nessun fornitore copre, il codice di un servizio che fornisce uno snippet HTML. Ogni script ha un nome, una categoria, una posizione, nell'head o alla fine del body, e il suo codice.

* Il codice è HTML: gli script e gli elementi di cui hanno bisogno, come l'immagine di un pixel di tracciamento. Niente viene caricato prima del consenso, nemmeno l'immagine.
* Gli script vengono eseguiti nel loro ordine, ogni file di script viene caricato prima che venga eseguito lo script successivo. Quando il consenso arriva dopo, vengono eseguiti senza ricaricare la pagina.
* Il codice della categoria necessaria viene stampato così com'è, in ogni pagina.
* Uno script che corrisponde a un fornitore della scheda **Blocco** segue anche il consenso a quel fornitore, con il [consenso per fornitore](blocco.md#consenso-per-fornitore).
* Dopo una revoca il codice non viene più eseguito dalla pagina successiva, la [pulizia dei cookie](tabella-cookie.md#i-cookie-senza-consenso-vengono-cancellati) cancella i cookie della categoria.

Solo gli utenti che possono salvare HTML non filtrato modificano gli script, come per il blocco HTML personalizzato: gli amministratori di un sito, i super amministratori di un network, e WP-CLI. Gli altri utenti vedono solo i loro nomi, e salvare le impostazioni lascia gli script come sono.

Il codice viene stampato con il plugin attivo, anche senza un fornitore nella scheda **Blocco**. Scrivi qui il codice di un fornitore, oppure bloccalo dove lo stampa il tema: non entrambi.

## Bloccare uno script

### Uno script accodato

Assegna una categoria allo script, viene stampato con `type="text/plain"` ed eseguito dopo il consenso:

```php
wp_enqueue_script( 'my-analytics', 'https://analytics.example.com/script.js', [], '1.0', true );
wp_script_add_data( 'my-analytics', 'consent_category', 'statistics' );
```

I suoi script inline, aggiunti con `wp_add_inline_script()`, attendono insieme a lui.

### Uno script nel contenuto o in un template

Scrivilo già bloccato, il plugin lo esegue dopo il consenso:

```html
<script type="text/plain" data-category="statistics">
	window.myTracker.start();
</script>
```

### Un fornitore

Un fornitore viene bloccato ovunque si trovino i suoi script e frame, in base agli indirizzi da cui carica e al testo dei suoi script inline:

```php
use ItalyCookieChoices\Blocking\Application\BuildingVendors;
use ItalyCookieChoices\Blocking\Domain\Vendor;

add_action( BuildingVendors::class, static function ( BuildingVendors $event ): void {
	$event->addVendor( new Vendor( 'matomo', 'statistics', [ 'matomo.example.com' ], [ '_paq.push' ] ) );
	$event->removeVendor( 'vimeo' );
	$event->allow( 'youtube.com/embed/public-video' );
} );
```

Chi gestisce il sito può aggiungere gli stessi fornitori dalla scheda **Blocco**, senza codice.

## Leggere il consenso

### In JavaScript

```js
window.italyCookieChoices.hasConsent( 'statistics' );
// La categoria e, con il consenso per fornitore, il fornitore non rifiutato.
window.italyCookieChoices.hasVendorConsent( 'youtube', 'marketing' );
window.italyCookieChoices.getConsent(); // { id, categories, version, date, refusedVendors? } o null

document.addEventListener( 'italy-cookie-choices:consent-changed', ( event ) => {
	const consent = event.detail.consent; // null quando viene revocato
} );

document.addEventListener( 'italy-cookie-choices:ready', ( event ) => {
	const consent = event.detail.consent;
} );
```

Un pulsante può accettare una categoria, o un solo fornitore con `data-icc-vendor`, o aprire le preferenze:

```html
<button type="button" data-icc-accept="marketing">Mostra il video</button>
<button type="button" data-icc-accept="marketing" data-icc-vendor="youtube">Mostra il video di YouTube</button>
<button type="button" data-icc-open-preferences>Preferenze cookie</button>
```

### In PHP

```php
if ( apply_filters( 'italy_cookie_choices_has_consent', false, 'statistics' ) ) {
	// Il visitatore ha accettato i cookie statistici.
}
```

Il controllo in PHP legge il cookie della richiesta: usalo solo nelle pagine che non sono in cache.

## Cambiare la policy e i testi

```php
use ItalyCookieChoices\Consent\Application\BuildingConsentPolicy;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use ItalyCookieChoices\Consent\UI\BuildingBanner;

add_action( BuildingConsentPolicy::class, static function ( BuildingConsentPolicy $event ): void {
	$event->addCategory( new Category( 'social' ) );
	// Una nuova versione chiede di nuovo il consenso.
	$event->changeVersion( new PolicyVersion( '2' ) );
} );

add_action( BuildingBanner::class, static function ( BuildingBanner $event ): void {
	$event->changeCategoryLabel( 'social', 'Social network', 'Pulsanti di condivisione e post incorporati.' );
	// La nota mostrata quando il browser invia il segnale Global Privacy Control.
	$event->changeText( BuildingBanner::GLOBAL_PRIVACY_CONTROL, 'Il tuo browser invia il GPC: il marketing resta disattivato.' );
} );
```

## Quando il plugin è caricato

```php
use ItalyCookieChoices\PluginLoaded;

add_action( PluginLoaded::class, static function ( PluginLoaded $event ): void {
	// Il plugin è caricato e i suoi servizi sono agganciati.
} );
```

L'azione `icc_plugin_loaded` delle versioni 2.x viene ancora eseguita, dopo l'evento.

## Tradurre i testi con un altro plugin multilingua

WPML e Polylang traducono i testi salvati senza configurazioni aggiuntive. Altri plugin, come qTranslate-XT, li traducono con `TranslatingText`:

```php
use ItalyCookieChoices\Settings\TranslatingText;

add_action( TranslatingText::class, static function ( TranslatingText $event ): void {
	$event->changeText( qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $event->text() ) );
} );
```
