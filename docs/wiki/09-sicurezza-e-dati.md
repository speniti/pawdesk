# Sicurezza e dati

Come PawDesk protegge i dati del salone e dei clienti: architettura del database, backup e procedura di ripristino. Pagina di riferimento per il **registro dei trattamenti** (GDPR art. 30–32).

## Dove vivono i dati

Tutti i dati (clienti, animali, appuntamenti, trattamenti) sono in un **database SQLite** dentro il volume persistente dell'applicazione su Dokploy:

```
/var/www/html/storage/app/private/database.sqlite
```

Il database viaggia sempre cifrato in transito (HTTPS) e il volume risiede sul server gestito da Dokploy. Il backup del volume verso R2 archivia il volume **per intero**, quindi nel bucket esiste anche il database live in chiaro: la riservatezza di quella copia dipende dalle ACL del bucket e dalla cifratura at-rest di Cloudflare. Ogni notte il comando `db:snapshot` aggiunge nel volume uno **snapshot cifrato** del database (AES-256-GCM): è l'unica copia la cui riservatezza **non** dipende dal provider, ed è l'unica con garanzia di consistenza (vedi sotto). Le uniche copie in chiaro temporanee vivono nella `/tmp` del container e vengono eliminate a fine run.

### Perché SQLite (e quando passa a PostgreSQL)

Lo stack usa SQLite in modalità **WAL** con *busy timeout* di 10 secondi: le letture non bloccano le scritture e viceversa, e le scritture concorrenti dell'app, della coda e dello scheduler attendono invece di fallire. Per un salone (poche decine di scritture al giorno) è la scelta più semplice e robusta: un solo file, backup atomici, nessun servizio database da mantenere.

PostgreSQL diventa la scelta giusta solo se: l'app gira su più server, la scrittura diventa intensiva e sostenuta, o serve replica/high-availability. La connessione `pgsql` è già configurata nel codice; la migrazione richiede però di ridefinire anche la strategia di backup (`db:snapshot` è progettata per SQLite).

## Backup

### Come funziona

Ogni notte alle **03:37** il comando `db:snapshot` esegue, in ordine:

1. **Snapshot consistente** — `VACUUM INTO` produce una copia atomica e compatta del database *mentre l'app continua a lavorare* (nessun rischio di copia a metà scrittura); contiene solo i dati **committati**.
2. **Cifratura client-side** — AES-256-GCM con chiave dedicata: lo snapshot nel volume e nel bucket è **ciphertext**; le copie in chiaro vivono solo temporaneamente nella `/tmp` del container.
3. **Retention** — nel volume restano gli ultimi 7 snapshot (configurabile).
4. **Verifica di ripristino** — l'ultimo snapshot viene decifrato, aperto e controllato: `integrity_check` del file + schema presente. È la verifica di ripristino automatizzata ex art. 32(1)(d) **a livello di integrità del file**, non un restore applicativo completo: quello si esegue con la procedura qui sotto.

Nota dimensionale: il comando tiene l'intero database in memoria per cifrarlo — adatto per ordine di grandezza a database fino a qualche decina di MB, come questo progetto. Se il database cresce molto oltre, valutare strumenti con cifratura in streaming (restic, age) o PostgreSQL.

Lo snapshot cifrato vive in `storage/app/private/backups/`, **dentro il volume**: il backup del volume verso R2 già configurato su Dokploy lo raccoglie automaticamente.

### In caso di fallimento

Se una fase fallisce, l'app invia una email all'indirizzo in `BACKUP_ALERT_EMAIL` con il motivo. A sistema ripristinato, controlla il flusso con:

```bash
php artisan db:snapshot --verify-only
```

### La chiave di cifratura (importante)

La chiave (`BACKUP_SNAPSHOT_KEY`) vive nel tab Environment di Dokploy, **sullo stesso server** del volume. Per lo scenario "host irrecuperabile" va quindi conservata anche **fuori host** (password manager del titolare). Senza la chiave gli snapshot sono irrecuperabili.

**Rotazione della chiave**: sostituire `BACKUP_SNAPSHOT_KEY` rende indecifrabili tutti gli snapshot esistenti — `db:snapshot --verify-only` fallirà fino a scadenza retention (~7 notti) senza che sia corruzione. Se serve ruotarla: decripta l'ultimo snapshot con la chiave vecchia, poi ricifralo con la nuova, oppure accetta consapevolmente la perdita dei backup in corso.

### Procedura di ripristino

In caso di perdita dati o server irrecuperabile:

1. Recupera l'ultimo snapshot cifrato dal bucket R2 (cartella `backups/` del volume).
2. Prendi la chiave dal password manager e decifra, dalla root dell'app (nel container `/var/www/html`):
   ```bash
   php -r "require 'vendor/autoload.php'; echo (new Illuminate\Encryption\Encrypter(base64_decode('CHIAVE_BASE64', true), 'AES-256-GCM'))->decryptString(file_get_contents('db-XXXX.sqlite.enc'));" > restored.sqlite
   ```
3. Nel volume **elimina** `database.sqlite-wal` e `database.sqlite-shm` insieme al vecchio `database.sqlite` (un WAL residuo verrebbe rieseguito sul file ripristinato e può corromperlo), poi copia `restored.sqlite` come nuovo `database.sqlite`.
4. Riavvia lo stack da Dokploy. Accedi a PawDesk e controlla clienti/appuntamenti: è la prova che il restore è riuscito. (`db:snapshot --verify-only` verifica invece l'integrità del prossimo snapshot, non del database ripristinato.)

### Configurazione (tab Environment di Dokploy)

| Variabile | Significato | Default |
|---|---|---|
| `BACKUP_SNAPSHOT_KEY` | Chiave AES-256 in base64 (32 byte) | vuoto = comando disattivo |
| `BACKUP_KEEP` | Snapshot da conservare nel volume | `7` |
| `BACKUP_ALERT_EMAIL` | Email avvisata a ogni fallimento | vuoto = nessun avviso |

Per generare la chiave: `php -r "echo base64_encode(random_bytes(32));"`

## Registro dei trattamenti (sintesi)

- **Titolare**: il salone (tenant). **Sistema**: PawDesk, gestito su server Dokploy dell'operatore.
- **Categorie di dati**: dati identificativi e di contatto dei clienti (nome, telefono E.164, indirizzo), dati degli animali (nome, specie, note anche sanitarie del trattamento), dati degli utenti del salone (nome, email).
- **Finalità**: gestione appuntamenti e trattamenti, promemoria via email/SMS, fatturazione.
- **Sicurezza (art. 32)**: autenticazione senza password (magic link monouso, rate-limit), HTTPS, isolamento per-tenant di tutte le query, backup cifrati offsite con verifica di ripristino automatica, log accessibili solo dal server.
- **Retention**: i dati restano finché il salone non li cancella; i trash/soft-delete seguono le impostazioni dell'app. Gli snapshot di backup: gli ultimi 7 nel volume, retention R2 secondo configurazione Dokploy.

## Email e SMS

Le notifiche (promemoria, conferme, magic link) partono via Mailgun e Vonage con le credenziali impostate in [Impostazioni](./07-impostazioni.md). Nessuna comunicazione promozionale: l'app non ha finalità di marketing.
