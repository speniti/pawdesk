# Trattamenti

Il trattamento è il record di ciò che è stato eseguito davvero su un animale: durata effettiva, prezzo finale, prodotti usati.

I trattamenti **non si creano a mano**: vengono generati automaticamente quando un appuntamento passa allo stato **Completato** (vedi [Calendario](./02-calendario-e-appuntamenti.md)). Ogni appuntamento ha al massimo un trattamento.

## Come viene generato

Al passaggio in **Completato**, PawDesk crea il trattamento collegato:

- **Durata effettiva (minuti)** = somma delle durate dei servizi dell'appuntamento;
- **Prezzo finale (€)** = somma dei prezzi applicati (quelli per taglia dove previsti);
- cliente e animale = quelli dell'appuntamento.

Se completi un appuntamento che avevi già completato in passato (dopo un cambio di stato), il trattamento esistente non viene sovrascritto: le modifiche fatte a mano restano.

## Modificare un trattamento

Apri il trattamento dall'elenco **Trattamenti** (o dalle schede *Trattamenti* di cliente e animale) e premi **Modifica**. Puoi rettificare:

- **Durata effettiva (minuti)** — la durata reale del lavoro;
- **Prezzo finale (€)** — l'importo incassato, se diverso dal totale dei servizi;
- **Visibile al cliente** — Sì/No, predefinito Sì;
- **Note** — fino a 1000 caratteri;
- **Prodotti utilizzati** — uno per riga (es. shampoo, balsamo).

## L'elenco

Colonne: **Data** (inizio dell'appuntamento), **Animale**, **Cliente**, **Durata effettiva**, **Prezzo finale**, **Visibile al cliente**. Ordine: dal più recente.

Se l'elenco è vuoto, non hai ancora completato appuntamenti: *I trattamenti vengono generati automaticamente al completamento degli appuntamenti.*

## Vista trattamento

- **Dettagli Trattamento** — durata effettiva, prezzo finale, **Servizi eseguiti** con prezzo e durata applicati per ciascuno, **Prodotti utilizzati**, **Note trattamento**.
- **Contesto** — data dell'appuntamento e link ad [animale](./04-animali.md) e [cliente](./03-clienti.md).

La **Spesa totale** mostrata nelle viste di cliente e animale (vedi [Clienti](./03-clienti.md), [Animali](./04-animali.md)) è la somma dei prezzi finali dei trattamenti.
