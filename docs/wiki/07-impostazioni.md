# Impostazioni

Configurazione del salone: orari, slot del calendario, invio email e SMS, dati per la privacy. Menu **Impostazioni**. Modificabile solo dagli amministratori.

Ogni sezione è ripiegabile; apri quella che ti serve, compila e premi **Salva** (conferma: *Impostazioni salvate*).

## Orari di apertura

Per ogni giorno della settimana, da **Lunedì** a **Domenica**, definisci una o più fasce orarie (es. mattina 09:00–13:00 e pomeriggio 14:30–19:00). Puoi aggiungere e rimuovere fasce.

- Lascia vuoto un giorno per indicare la chiusura.
- Questi orari determinano le fasce evidenziate nel [calendario](./02-calendario-e-appuntamenti.md).

## Configurazione slot

- **Durata slot (minuti)** — la griglia su cui si agganciano gli appuntamenti nel calendario. Predefinito 30.
- **Buffer tra appuntamenti (minuti)** — margine tra un appuntamento e l'altro. Predefinito 15.

## Email (Mailgun)

Credenziali per l'invio delle email ai clienti:

- **API Key** e **Dominio** — i due dati obbligatori perché l'invio funzioni;
- **Regione Mailgun** — Stati Uniti (predefinita) o Europa;
- **Indirizzo mittente** e **Nome mittente** — come compaiono nelle email.

Mailgun serve per **tutte** le email ai clienti: link di accesso, notifiche degli appuntamenti, promemoria e informativa privacy. Senza **API Key** e **Dominio** configurati le email ai clienti vengono scartate: PawDesk le registra come saltate e non ritenta. In ambiente locale le email restano consegnabili per prova, senza credenziali.

## Dati del titolare del trattamento

Dati che compaiono nell'informativa privacy inviata ai clienti (art. 13 GDPR):

- **Ragione sociale**
- **Titolare / Legale rappresentativo**
- **Partita IVA**
- **Sede legale**
- **Email contatto privacy**
- **Telefono**

Compilali prima di usare l'azione **Informativa Privacy** sui clienti (vedi [Clienti](./03-clienti.md)). Se ragione sociale o email di contatto sono vuote, l'informativa usa il nome del salone e l'indirizzo mittente delle email.

## SMS (Vonage)

Credenziali per l'invio di SMS ai clienti che preferiscono questo canale:

- **API Key** e **API Secret**;
- **Mittente SMS** — obbligatorio se inserisci una API key; al massimo 11 caratteri, è il mittente che il cliente vede (es. `PawDesk.`).

Senza questa sezione configurata i clienti ricevono tutto via email, anche chi preferisce gli SMS (vedi [Preferenze Comunicazione](./03-clienti.md)).
