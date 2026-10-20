# PawDesk

## Scheduler (cron)

I reminder degli appuntamenti (24h e 1h prima) vengono inviati dal comando
`appointments:send-reminders`, pianificato ogni 15 minuti dallo scheduler di
Laravel (`routes/console.php`, insieme alla pulizia settimanale dei job
falliti). Il comando è idempotente: ogni reminder viene inviato una sola volta
per appuntamento (tracciato in `notification_logs`).

In produzione lo stack Docker (`docker-compose.prod.yml`, usato da Dokploy
come Compose file path) include già i processi necessari:

- `scheduler`: `php artisan schedule:work` (esegue i task dovuti ogni minuto)
- `worker`: `php artisan queue:work` — le notifiche passano dalla coda
  (`QUEUE_CONNECTION=database`)
- `migrate`: one-shot `php artisan migrate --force` prima di app/worker/scheduler
- `app`: FrankenPHP sulla porta interna 8080

Prerequisiti sul server: la rete esterna `dokploy-network` (creata da Dokploy)
e un `.env` valido (via tab Environment di Dokploy), con `MAIL_MAILER=smtp` e
credenziali per l'invio dell'email di login.

Verifica:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan schedule:list
docker compose -f docker-compose.prod.yml exec app php artisan appointments:send-reminders
docker compose -f docker-compose.prod.yml logs worker scheduler
grep "Appointment reminders dispatched" storage/logs/laravel.log
```
