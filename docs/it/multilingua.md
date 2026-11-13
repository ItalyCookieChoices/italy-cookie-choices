# Siti multilingua

I testi del plugin, come i pulsanti e i nomi predefiniti delle categorie, sono tradotti nella lingua del sito. Il plugin include la traduzione italiana, le altre lingue arrivano da [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/italy-cookie-choices/).

I testi che scrivi nelle impostazioni vengono tradotti con il tuo plugin multilingua.

## WPML

Apri **WPML > Traduzione stringhe** e scegli il dominio **Italy Cookie Choices**, il nome delle versioni 2.x mantenuto per le traduzioni salvate. Le stringhe vengono registrate ogni volta che un amministratore apre la bacheca:

* Banner text
* Banner url
* Banner anchor text
* Banner button text
* Content message text
* Content message button text
* Floating button label
* Category *id* label e Category *id* description, per i testi delle categorie che hai cambiato

## Polylang

Apri **Lingue > Traduzioni** e filtra il gruppo **Italy Cookie Choices**. Le stringhe sono le stesse di WPML.

## TranslatePress

TranslatePress traduce tutta la pagina, anche il banner e le preferenze: non serve nessuna impostazione. I testi che scrivi nelle impostazioni e il link alla policy sono nell'editor delle traduzioni con gli altri testi della pagina, e il link alla policy porta alla pagina nella lingua del visitatore. I pulsanti e i nomi delle categorie arrivano dalla traduzione del plugin in ogni lingua, come gli altri testi di WordPress.

Il banner appare solo prima di una scelta: per tradurlo nell'editor visuale, apri l'editor in una finestra privata del browser, o cancella il cookie `icc_consent`.

## Weglot

Anche Weglot traduce tutta la pagina, banner e preferenze compresi: la sua traduzione automatica aggiunge i loro testi all'elenco delle traduzioni della dashboard di Weglot, dove puoi cambiarli. Non serve nessuna impostazione del plugin.

## MultilingualPress

MultilingualPress tiene ogni lingua in un suo sito di una [rete multisito](multisito.md), e ogni sito ha le sue impostazioni: non serve tradurre le stringhe. Su ogni sito di una lingua, apri **Cookie Choices** e scrivi i testi nella sua lingua, con il link alla sua pagina della policy.

* Con le impostazioni del sito principale, ogni sito mantiene comunque i suoi testi.
* I pulsanti e i nomi delle categorie arrivano dalla traduzione del plugin nella lingua di ogni sito.
* Su una rete con sottocartelle, come `example.com/de/`, il consenso vale per tutte le lingue. Con i sottodomini, come `de.example.com`, scrivi il dominio principale nel **Dominio dei cookie** di ogni sito, vedi la [Guida introduttiva](guida-introduttiva.md#banner). Con un dominio per ogni lingua, il visitatore sceglie di nuovo su ogni dominio.

### La scheda Lingue

Con MultilingualPress attivo, la pagina delle impostazioni ha una scheda **Lingue**, con i siti collegati a questo che amministri.

* **I testi di questo sito**: scegli un sito collegato in **Compila da**, poi **Copia i testi vuoti** o **Traduci i testi vuoti**. Vengono compilati solo i testi vuoti, nel modulo: controllali e salva le modifiche.
* **Una scheda per ogni sito collegato**: modifica i suoi testi e salvali con **Salva i testi di** quel sito. **Copia i testi vuoti da questo sito** e **Traduci i testi vuoti da questo sito** compilano i suoi testi vuoti da questo sito.
* **Traduci** usa la traduzione automatica di MultilingualPress, disponibile quando in MultilingualPress è configurato un servizio di traduzione, come DeepL, per le due lingue. I testi del banner sono testi legali: leggi la traduzione prima di salvarla.
* **Il link alla policy non viene mai compilato**: la policy può cambiare tra i paesi. Quando il link è vuoto e la pagina della policy di un sito collegato ha una traduzione su questo sito, la scheda propone il suo indirizzo con **Usa questo link**.

Quando MultilingualPress crea un sito o cambia i suoi collegamenti, un sito collegato senza i suoi testi mostra un avviso ai suoi amministratori: **Compila i testi** apre la scheda Lingue, **Ignora** nasconde l'avviso. Niente cambia senza l'amministratore.

## Altri plugin

Altri plugin, come qTranslate-XT, traducono i testi con l'evento `TranslatingText`. Vedi [Integrazioni](integrazioni.md#tradurre-i-testi-con-un-altro-plugin-multilingua).
