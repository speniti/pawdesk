<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('appointments:send-reminders')
    ->everyFifteenMinutes()
    // Il task dura pochi secondi: una scadenza esplicita limita il recovery se
    // lo scheduler viene interrotto a metà run (il default del lock è 24h).
    ->withoutOverlapping(15)
    ->onOneServer();

Schedule::command('queue:prune-failed --hours=168')->daily();

// Snapshot cifrato del database nel volume, raccolto dal backup volume→R2 di
// Dokploy (#75). Orario fuori dalla griglia dei reminder (ogni 15 minuti);
// la verifica restore gira a ogni snapshot, quindi un fallimento arriva via
// email all'admin. Senza chiave configurata il task viene saltato, non
// fallito (di norma in sviluppo locale).
Schedule::command('db:snapshot')
    ->dailyAt('03:37')
    ->withoutOverlapping(30)
    ->when(fn (): bool => filled(config('backup.key')));
