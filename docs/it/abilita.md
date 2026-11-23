# Abilità per gli strumenti di IA

[Read in English](../abilities.md).

Da WordPress 6.9, i plugin descrivono cosa sanno fare con l'[API Abilities](https://developer.wordpress.org/apis/abilities/): ogni abilità ha un nome, una descrizione, la forma dei dati in ingresso e in uscita, e chi può eseguirla. Gli assistenti di IA collegati con l'[MCP Adapter](https://github.com/WordPress/mcp-adapter) e gli strumenti di automazione trovano le abilità e le eseguono come l'utente collegato, senza conoscere la pagina delle impostazioni.

Cookie Choices registra le sue abilità nella categoria **Cookie Choices**. Possono eseguirle solo gli amministratori, gli utenti con la capability `manage_options`, come per la pagina delle impostazioni. Da WordPress 6.5 a 6.8 non viene registrato nulla.

## Le abilità

| Nome | Cosa fa | Modifica il sito |
| --- | --- | --- |
| `italy-cookie-choices/get-status` | Restituisce lo stato del widget della bacheca: cosa rende valido il consenso, `warning` per ciò che manca. | No |
| `italy-cookie-choices/get-settings` | Restituisce le impostazioni salvate, con i nomi dell'esportazione della pagina delle impostazioni. | No |
| `italy-cookie-choices/list-vendors` | Elenca i fornitori comuni e quelli personalizzati, la loro categoria e se sono bloccati. | No |
| `italy-cookie-choices/get-consent-statistics` | Conta le risposte al banner per mese, dal [registro dei consensi](registro-consensi.md). | No |
| `italy-cookie-choices/scan-site` | Legge dieci delle pagine scelte e trova i loro fornitori e le terze parti sconosciute, come **Scansiona il sito**. | No |
| `italy-cookie-choices/get-scan-report` | Restituisce il report dell'ultima scansione programmata. | No |
| `italy-cookie-choices/list-migration-sources` | Elenca gli altri plugin per il consenso da importare, con cosa cambia ogni importazione. | No |
| `italy-cookie-choices/update-settings` | Modifica alcune impostazioni, controllate come nella pagina delle impostazioni. | Sì |
| `italy-cookie-choices/switch-vendor` | Attiva o disattiva un fornitore comune, come **Blocco > Fornitori comuni**. | Sì |
| `italy-cookie-choices/add-custom-vendor` | Aggiunge un fornitore personalizzato, come **Blocco > Fornitori personalizzati**. | Sì |
| `italy-cookie-choices/add-site-cookie` | Aggiunge un cookie del sito alla [tabella dei cookie](tabella-cookie.md), come **Blocco > Cookie del sito**. | Sì |
| `italy-cookie-choices/import-settings` | Importa le impostazioni di un altro plugin per il consenso, come **Strumenti > Importa da un altro plugin**. | Sì |

Ogni abilità dice agli strumenti di IA se legge soltanto, se può sostituire ciò che è salvato e se eseguirla due volte non cambia altro. Per configurare il plugin con un agente di IA, vedi [Configurare Cookie Choices con un agente di IA](agente-ai.md).

### update-settings

L'input ha solo le impostazioni da cambiare, con i nomi di `get-settings`. Una casella è attiva con `"1"`, `null` rimuove un'impostazione, come una casella disattivata, e un elenco sostituisce quello salvato. Le impostazioni sono controllate come nella pagina delle impostazioni: un valore non valido non viene salvato, e l'abilità restituisce le impostazioni salvate. Su un sito di una rete che usa le impostazioni del sito principale cambiano solo i testi del sito.

```json
{ "text": "Usiamo i cookie per misurare le visite.", "consent_log": "1", "reload": null }
```

### scan-site

L'input è la selezione di **Scansiona il sito**: `home`, `postTypes`, `categories` e `posts`, con gli id. Ogni esecuzione legge dieci pagine: eseguila di nuovo con `offset` uguale al `nextOffset` che restituisce, finché `nextOffset` è `null`.

```json
{ "home": true, "postTypes": ["page"], "offset": 0 }
```

### import-settings

L'input è l'id di un plugin da `list-migration-sources`, che mostra cosa cambia l'importazione prima di eseguirla. I testi sostituiscono quelli salvati, i fornitori, i cookie e gli script vengono aggiunti.

```json
{ "source": "complianz" }
```

### add-site-cookie

L'input è il nome del cookie, la sua categoria, `necessary`, `preferences`, `statistics` o `marketing`, la sua durata e la sua finalità. Il nome deve essere nuovo.

```json
{ "name": "wp_lang", "category": "necessary", "duration": "Sessione", "purpose": "Ricorda la lingua scelta." }
```

### switch-vendor

In ingresso c'è l'id di un fornitore comune, da `list-vendors`, ed `enabled`: `true` blocca i suoi script e frame fino al consenso, `false` smette di bloccarli.

```json
{ "id": "youtube", "enabled": false }
```

### add-custom-vendor

In ingresso c'è il nome del fornitore, la categoria di consenso che attende, `preferences`, `statistics` o `marketing`, e i pattern: testi degli indirizzi dei suoi script e frame, o dei suoi script inline. L'id viene creato dal nome, come nella pagina delle impostazioni, e deve essere nuovo.

```json
{ "name": "Chat Widget", "category": "preferences", "patterns": ["chat.example.com"] }
```

Restituisce il fornitore come viene salvato. Aggiungi i suoi cookie per la [tabella dei cookie](tabella-cookie.md) nella pagina delle impostazioni.

## L'API REST

Le abilità sono anche nell'API REST di WordPress, per gli amministratori collegati o con una [password per le applicazioni](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#application-passwords):

* `GET /wp-json/wp-abilities/v1/abilities?category=italy-cookie-choices` le elenca.
* `GET /wp-json/wp-abilities/v1/abilities/italy-cookie-choices/list-vendors/run` esegue un'abilità che legge soltanto.
* `POST /wp-json/wp-abilities/v1/abilities/italy-cookie-choices/switch-vendor/run` con `{"input": {"id": "youtube", "enabled": false}}` esegue un'abilità che modifica il sito.
