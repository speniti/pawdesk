# Piano: fix urgenti dalla code review del 2026-11-22

Branch: **uno per issue**, `fix/<issue>-<slug>`, sempre da `main`. Ogni issue = un ciclo completo autonomo: implementazione (TDD) → **review con 4 agenti** (@code-reviewer, @laravel-specialist, @security-engineer, @test-automator) in parallelo sul diff → fix dei finding + riverifica → **PR** che referenzia la issue (es. `fix: enforce appointment status machine (#78)`). Solo dopo la PR si passa alla issue successiva.

## Contesto

pawdesk — Laravel 13 / Filament 5.11 / Pest v4, multi-tenant (global scope Filament + policy `belongsToCurrentTenant` + guard esplicite fail-closed). Le issue #71-#86 derivano dalla review congiunta laravel-specialist + security-engineer + test-automator. Decisioni di product già prese: **#71 → riverifica cambio email via magic link**; **#77 → rimozione completa del consenso marketing**.

## Global Constraints

- **TDD**: ogni fix parte dal test rosso (RED documentato), poi implementazione, poi verde. Evidenza RED/GREEN nel report.
- **Tenancy**: ogni query/form/path nuovo o toccato dev'essere fail-closed rispetto al tenant corrente; niente fiducia nello scope implicito dove il codice esce dal panel.
- **Convenzioni test**: Pest v4, sqlite `:memory:`, factory con state `forTenant()`, pattern dei test esistenti (es. `AppointmentTenantIsolationTest`, `TreatmentPolicyTest`, `AppointmentCalendarTest:120-163` per i cross-tenant).
- `vendor/bin/pint` e `vendor/bin/phpstan analyse` (livello 5) devono restare puliti su ogni commit.
- Niente refactoring fuori dallo scope della issue (YAGNI).
- Suite completa verde prima di ogni commit; run focalizzati durante l'iterazione.

## Task 1: #78 — enforce macchina a stati appuntamenti

`AppointmentForm.php:96-101` espone un Select con tutti gli `AppointmentStatus`; l'`EditAction` del calendario (`AppointmentCalendar.php:124-148`) salva senza validare `canTransitionTo` (check presente solo in `transitionStatus:305`). In creazione lo status è impostabile arbitrariamente (es. `completed` con totali a zero).

- In `EditAction->using()` (o equivalente): rifiutare la modifica dello status se `$record->status->canTransitionTo($nuovo)` è falso; oppure filtrare le opzioni del Select in edit a `$status->nextStatuses()`. La soluzione deve comunque consentire salvare il record senza cambiare status.
- In creazione: limitare gli status iniziali ammessi (es. solo gli stati "iniziali" leciti per la macchina a stati — verificare `AppointmentStatus` per la semantica esistente).
- Test: transizione illegale via form rifiutata (nessun treatment generato, nessuna notifica spedita); transizione legale consentita; creazione con status non iniziale rifiutata.
- Rif. issue #78 su speniti/pawdesk.

## Task 2: #79 — cancellazione utente multi-tenant non distruttiva

`UsersTable.php:93-95` DeleteAction/DeleteBulkAction cancellano il record `User` (cascade su `tenant_user`): un admin del salone A rimuove l'accesso anche al salone B.

- Se l'utente appartiene ad altri tenant oltre a quello corrente: la delete diventa `detach()` dal tenant corrente, con conferma esplicita (label/modal che lo dichiari).
- Cancellazione fisica solo se l'utente non ha altri tenant (comportamento attuale).
- Test: delete dal tenant A con utenza anche in B → utente esiste ancora e conserva B; delete dell'utenza mono-tenant → cancellata.
- Rif. issue #79.

## Task 3: #77 — rimozione completa consenso marketing

Decisione di product: rimuovere la funzione (non reintrodurre il campo).

- Rimuovere: lettura `$data['marketing_consent']` in `CreateCustomer.php:21-23` (e analogo in edit se presente), filtro `CustomersTable.php:58-64`, icona `CustomerInfolist.php:77-81`.
- Migrazione per droppare indice `(tenant_id, marketing_consent_at)` e colonna `marketing_consent_at` da `customers`.
- Aggiornare test/factory/documentazione che referenziano il consenso (grep `marketing_consent` su tutto il repo).
- Rif. issue #77.

## Task 4: #82 — test isolamento tenant (policy + risorse)

Solo test, nessuna modifica a codice di produzione (se un test scopre un bug reale: fermarsi e riportare, non fixare in questo task).

- `tests/Feature/Policies/AppointmentPolicyTest.php`: matrice ruolo (Admin vs Staff per delete) + `admin tenant A → can('update'/'delete', appointment tenant B) === false` + `create`/`viewAny` per utente senza tenant. Pattern: `TreatmentPolicyTest`.
- Cross-tenant per le risorse (trattamenti in primis): lista del tenant A `assertDontSee` record del tenant B (pattern `AppointmentCalendarTest.php:120-163`) + `Livewire::test(EditTreatment::class, ['record' => $foreignId])` → 404. Estendere a pets e services dove il pattern è applicabile; view page customer/pet: edit foreign → 404.
- Rif. issue #82.

## Task 5: #72 — trusted proxies + rate limit per-email

- `bootstrap/app.php`: configurare `trustProxies` (subnet Docker/Traefik — usare variabile d'ambiente configurabile con default sicuro per non rompere dev/CI, es. `TRUSTED_PROXIES` env).
- Rate limiter **nominato per-email** sull'invio dei magic link (oltre al throttle per-IP esistente su `PawDeskProvider.php:49-51`).
- Test: throttling per-email scatta dopo N richieste con IP diversi; trustProxies rispetta la config.
- Rif. issue #72.

## Task 6: #71 — cambio email con riverifica magic link

Feature di sicurezza (la più corposa). Flusso deciso con l'utente:

- Il form del profilo resta modificabile, ma il cambio NON è immediato: al salvage con email diversa, si invia un magic link di conferma al **nuovo** indirizzo e una notifica al **vecchio**.
- Il click sul link (pagina/token dedicati, stesso stile del `MagicLinkService` esistente: token 64 char, hash sha256 in cache, single-use, TTL) applica il cambio.
- La sessione corrente non cambia email finché il link non è confermato; messaggi generici anti-enumeration come il flusso esistente.
- Riutilizzare/estendere `MagicLinkService` e i canali di notifica esistenti; niente nuova infrastruttura.
- Test: flusso completo (richiesta → conferma → email aggiornata + notifica vecchio indirizzo); token scaduto/single-use; email in uso da altro utente rifiutata (unicità globale).
- Rif. issue #71.

## Task 7: #86 (parte codice) — workflow audit schedulato

- Workflow GitHub Actions schedulato settimanale con `composer audit --locked` (+ `npm audit --omit=dev` se sensato), che apra issue/falli visibilmente in caso di advisory.
- L'aggiornamento dipendenze (laravel 13.43 / filament 5.12) NON fa parte di questo task: richiede PHP 8.5 per la risoluzione e va fatto a fine branch per evitare churn del lock.
- Rif. issue #86.
