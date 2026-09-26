# Servizi

Catalogo dei servizi offerti dal salone. Menu **Servizi**.

Ogni servizio definisce **prezzo base** e **durata base**. Prezzo e durata effettivi possono variare per combinazione di **taglia** e **tipo di pelo** dell'animale: si applica la combinazione che corrisponde esattamente all'animale, in alternativa prezzo e durata base.

## Creare un servizio

1. Da **Servizi** premi **Nuovo servizio**.
2. Sezione **Informazioni generali**:
   - **Nome** — obbligatorio;
   - **Descrizione** — facoltativa;
   - **Categoria** — obbligatoria: Grooming, Bagno, Tosatura, Benessere, Specialità;
   - **Tipo di manto** — facoltativo: i servizi con un manto impostato sono offerti solo agli animali con quel tipo di pelo;
   - **Durata base (minuti)** — obbligatoria, da 1 a 480; predefinita 60;
   - **Prezzo base (€)** — obbligatorio, usato quando nessuna combinazione corrisponde all'animale;
   - **Stato** — **Attivo** (predefinito) o **Archiviato**;
   - **Combinabile con altri servizi** — Sì/No, predefinito Sì.
3. Sezione **Prezzi e durate per combinazione**: usa **Aggiungi una combinazione** per ogni coppia taglia + tipo di pelo con prezzo e durata dedicati (es. Medio · Pelo lungo 35 €, 75 min). Ogni combinazione può comparire una sola volta.
4. Premi **Crea**.

## Come vengono usati prezzi e durate

- Se esiste una combinazione taglia + tipo di pelo identica a quella dell'animale, si applicano il suo prezzo e la sua durata; altrimenti il **Prezzo base** e la **Durata base**.
- Gli animali senza tipo di pelo registrato ricevono sempre prezzo e durata base.
- Nel form dell'appuntamento, se l'animale ha un tipo di pelo, l'elenco **Servizi** mostra solo i servizi senza vincolo di manto o compatibili con quel manto.
- Il **Riepilogo Costi** dell'appuntamento (vedi [Calendario](./02-calendario-e-appuntamenti.md)) mostra prezzo e durata effettivi per ogni servizio in base all'animale selezionato.

## Note d'uso

- Nella tabella il filtro **Stato** è preimpostato su **Attivo**: gli archiviati compaiono solo cambiando il filtro.
- Solo i servizi attivi sono selezionabili nei nuovi appuntamenti. Archiviare un servizio lo toglie dal catalogo senza toccare lo storico.
- L'elenco è ordinato per categoria; filtri disponibili: **Categoria**, **Manto**, **Stato**.
