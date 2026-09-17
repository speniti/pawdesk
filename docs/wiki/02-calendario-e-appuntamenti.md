# Calendario e appuntamenti

La dashboard di PawDesk è il calendario del salone (menu **Calendario**): qui si creano, spostano e chiudono tutti gli appuntamenti. Non esiste una pagina separata "Appuntamenti": tutto passa dal calendario.

## La vista

- All'apertura si vede la **settimana**; dai selettori in alto puoi passare alla vista **giorno** o **mese**.
- Naviga con **precedente**, **successivo** e **oggi**; il pulsante **Aggiorna** ricarica gli eventi.
- Le fasce di apertura del salone (configurate in [Impostazioni](./07-impostazioni.md)) sono evidenziate; il resto della griglia resta in ombra. I giorni senza orari sono giorni di chiusura.
- La griglia segue la **durata dello slot** del salone (predefinita 30 minuti).
- Ogni appuntamento è etichettato «nome animale - nome cliente» e colorato secondo lo stato:

| Stato | Colore |
|---|---|
| Richiesto | grigio |
| Confermato | azzurro |
| In corso | giallo |
| Completato | verde |
| Annullato | rosso |
| Non presentato | rosso |

## Creare un appuntamento

1. Clicca su uno slot libero del calendario: si apre il pannello di creazione con **Inizio** e **Fine** già impostati sullo slot scelto.
2. Sezione **Cliente e Animale**:
   - **Cliente** — obbligatorio, cerca per nome o cognome;
   - **Animale** — obbligatorio; l'elenco mostra solo gli animali del cliente selezionato;
   - **Toelettatore** — opzionale, uno degli operatori del salone.
3. Sezione **Data e Ora**: imposta **Inizio**. **Fine** è calcolata automaticamente e non è modificabile: è la somma delle durate dei servizi selezionati.
4. Sezione **Dettagli**:
   - **Stato** — predefinito **Richiesto**;
   - **Servizi** — solo i servizi attivi del catalogo; per ogni servizio il **Riepilogo Costi** mostra in tempo reale prezzo e durata, con il prezzo della taglia dell'animale quando previsto, più il totale e la durata complessiva;
   - **Note interne** — fino a 1000 caratteri, visibili solo allo staff.
5. Premi **Crea**.

## Visualizzare e modificare

- Clicca su un appuntamento: si apre il pannello di visualizzazione con tutti i dettagli.
- Da lì puoi aprire **Modifica**; in modifica, nel footer, trovi anche **Elimina** (a destra).
- Trascina un appuntamento per spostarlo, oppure trascina il bordo per cambiarne la durata: gli orari si aggiornano automaticamente.

## Stati e transizioni

Il ciclo di vita previsto è:

**Richiesto → Confermato → In corso → Completato**

Transizioni ammesse:

- **Richiesto** → Confermato, Annullato
- **Confermato** → In corso, Annullato, Non presentato
- **In corso** → Completato, Annullato, Non presentato

**Completato**, **Annullato** e **Non presentato** sono stati terminali: da qui l'appuntamento non può più cambiare stato e non si può spostare o ridimensionare trascinandolo.

Per cambiare stato:

1. Apri l'appuntamento (visualizzazione o modifica).
2. Nel footer trovi un pulsante per ogni stato raggiungibile (es. **Confermato**).
3. Conferma l'avviso (*Sei sicuro di voler cambiare lo stato dell'appuntamento in …?*).

Al passaggio in **Completato** PawDesk genera automaticamente il [trattamento](./06-trattamenti.md) con durata e prezzo precompilati.

## Notifiche al cliente

Le notifiche sono automatiche, non vanno inviate a mano:

- al passaggio in **Confermato**, **Annullato** o **Completato**;
- promemoria 24 ore e 1 ora prima dell'inizio, per gli appuntamenti confermati.

Il canale dipende dalle preferenze del cliente (vedi [Clienti](./03-clienti.md)): email, oppure SMS se il cliente preferisce gli SMS e il salone ha configurato Vonage in [Impostazioni](./07-impostazioni.md).
