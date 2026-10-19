<?php

declare(strict_types=1);

test('schedules appointment reminders every fifteen minutes', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('appointments:send-reminders')
        ->assertSuccessful();
});

test('prunes failed jobs weekly', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('queue:prune-failed --hours=168')
        ->assertSuccessful();
});
