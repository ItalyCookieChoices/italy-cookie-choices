# Configurare Cookie Choices con un agente di IA

[Read in English](../ai-agent.md).

Un assistente di IA, come Claude, può configurare Cookie Choices dall'inizio alla fine: scansiona il sito, blocca i fornitori che trova, scrive i testi e i link del banner, importa le impostazioni di un altro plugin per il consenso e ti dice cosa manca ancora. Usa le [abilità](abilita.md) del plugin, attraverso l'[MCP Adapter](https://github.com/WordPress/mcp-adapter) di WordPress.

## Cosa ti serve

* WordPress 6.9 o successivo, con l'API Abilities.
* Il plugin MCP Adapter, installato e attivo.
* Un account amministratore: le abilità vengono eseguite come l'utente che si collega, solo con la capacità `manage_options`.
* Un client di IA che supporta MCP, come Claude Desktop o Claude Code.

## Collegare il client di IA

L'MCP Adapter ha un server predefinito che mostra le abilità pubbliche dei plugin. Collega il tuo client in uno dei due modi, la [guida all'uso da CLI](https://github.com/WordPress/mcp-adapter/blob/trunk/docs/guides/cli-usage.md) dell'MCP Adapter ha la configurazione di ogni client:

* **Sul tuo computer, con WP-CLI**: il client esegue `wp mcp-adapter serve --user=admin` nella cartella del sito.
* **Su un sito remoto, via HTTP**: il client esegue il proxy `@automattic/mcp-wordpress-remote` con l'indirizzo `https://example.com/wp-json/mcp/mcp-adapter-default-server`, il tuo nome utente e una [password per le applicazioni](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/).

Il client trova le abilità della categoria **Cookie Choices** con lo strumento `mcp-adapter/discover-abilities` e le esegue con `mcp-adapter/execute-ability`.

## Un esempio di prompt

```text
Configura Cookie Choices sul mio sito:
1. Controlla get-status e dimmi cosa manca.
2. Se list-migration-sources trova un altro plugin per il consenso, mostrami cosa cambia l'importazione e aspetta il mio OK.
3. Scansiona la home page e le pagine con scan-site, poi blocca i fornitori che trova con switch-vendor.
4. Per le terze parti sconosciute, dimmi quali impostano cookie prima di aggiungerle come fornitori personalizzati.
5. Imposta il link alla policy su /privacy-policy/ e attiva il registro dei consensi con update-settings.
6. Controlla di nuovo get-status e fammi un breve riepilogo.
```

## Mantenere il controllo

* Leggi prima di approvare: il client di IA chiede prima di eseguire un'abilità che modifica il sito, e le abilità dicono se leggono soltanto o se possono sostituire ciò che è salvato.
* Le modifiche sono le stesse della pagina delle impostazioni, controllate nello stesso modo: apri la pagina delle impostazioni per rivederle.
* `update-settings` e `import-settings` possono sostituire i testi salvati: esporta prima le impostazioni da **Strumenti**, per ripristinarle se serve.
* L'agente di IA non scrive la tua cookie policy e non è una consulenza legale: verifica con il tuo consulente di cosa ha bisogno il tuo sito.
