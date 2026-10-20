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
