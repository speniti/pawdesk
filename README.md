# PawDesk

## Scheduler (cron)

I reminder degli appuntamenti (24h e 1h prima) vengono inviati dal comando
`appointments:send-reminders`, pianificato ogni 15 minuti dallo scheduler di
Laravel (`routes/console.php`). Il comando è idempotente: ogni reminder viene
inviato una sola volta per appuntamento (tracciato in `notification_logs`).

Per farlo girare in produzione serve una cron entry che esegua lo scheduler
ogni minuto. Su Dokploy configura un cron job (o una "Scheduled Task") con:

```
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

Le notifiche passano dalla coda (`QUEUE_CONNECTION=database`): assicurati che
giri anche un worker, ad esempio `php artisan queue:work` come servizio/daemon.

Verifica:

```bash
php artisan schedule:list                        # voce appointments:send-reminders
php artisan appointments:send-reminders          # esecuzione manuale
grep "Appointment reminders dispatched" storage/logs/laravel.log
```
