# Clienti

Anagrafica dei clienti del salone: recapiti, note, consensi privacy, animali e trattamenti collegati. Menu **Clienti**.

## Creare un cliente

1. Da **Clienti** premi **Nuovo cliente**.
2. Sezione **Dati anagrafici**: **Nome** e **Cognome** (obbligatori).
3. Sezione **Recapiti**:
   - **Email** — obbligatoria; non può essere già usata da un altro cliente del salone;
   - **Telefono** — obbligatorio, in formato internazionale E.164 con prefisso paese, es. `+393331234567`;
   - **Indirizzo** — cerca e seleziona dalla mappa.
4. Sezione **Note** (facoltative): annotazioni sul cliente.
5. Premi **Crea**.

## La tabella

Colonne: nome completo, email, telefono, canale preferito. Ricerca per nome ed email; azioni **Visualizza** e **Modifica** nel menu ⋮ di ogni riga; eliminazione anche in massa dalla selezione.

Filtri disponibili:

- **Canale preferito** — email, SMS o WhatsApp;
- **Consenso marketing** — con consenso, senza consenso;
- **Senza appuntamenti** — clienti che non hanno mai preso un appuntamento.

## Vista cliente

Statistiche in alto: **Spesa totale**, **Appuntamenti** (numero) e **Pets** (numero di animali).

Sezioni:

- **Recapiti** — email, telefono, indirizzo;
- **Note**;
- **Comunicazione** — canale preferito;
- **Privacy e consensi** — se l'informativa privacy è stata inviata e se c'è consenso marketing.

Schede collegate:

- **Pets** — animali del cliente, con azione **Nuovo pet**;
- **Trattamenti** — trattamenti eseguiti, in ordine cronologico inverso.

## Altre Azioni

Dal cliente in **Modifica**, il menu **Altre Azioni** (⋮ in alto) contiene:

- **Informativa Privacy** — invia per email l'informativa privacy (GDPR) e registra la data d'invio nella sezione *Privacy e consensi*. Serve l'email del cliente e la configurazione Mailgun del salone (vedi [Impostazioni](./07-impostazioni.md)); in ambiente locale l'invio funziona anche senza configurazione. Se qualcosa manca, PawDesk avvisa senza inviare.
- **Comunicazione** — apre **Preferenze Comunicazione**: scegli il canale preferito tra **Email**, **SMS** e **WhatsApp** e conferma con **Modifica**. Gli invii automatici usano email o SMS; WhatsApp è registrato come preferenza.
- **Elimina** — elimina il cliente.

