<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

function scheduledEvent(string $command): ?Event
{
    return collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, $command));
}

test('schedules appointment reminders every fifteen minutes', function () {
    $this->artisan('schedule:list')->assertSuccessful();

    $event = scheduledEvent('appointments:send-reminders');

    expect($event)->not->toBeNull();
    expect($event->getExpression())->toBe('*/15 * * * *');
});

test('prunes failed jobs weekly', function () {
    $this->artisan('schedule:list')->assertSuccessful();

    $event = scheduledEvent('queue:prune-failed');

    expect($event)->not->toBeNull();
    expect($event->getExpression())->toBe('0 0 * * *');
    expect((string) $event->command)->toContain('--hours=168');
});

test('runs the nightly encrypted database snapshot', function () {
    $this->artisan('schedule:list')->assertSuccessful();

    $event = scheduledEvent('db:snapshot');

    expect($event)->not->toBeNull();
    expect($event->getExpression())->toBe('37 3 * * *');
    // senza withoutOverlapping un run lungo raddoppia gli snapshot se lo
    // scheduler riparte a metà; expiresAt è in MINUTI (finestra recovery 30).
    expect($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(30);

    // ->when: senza chiave configurata il task viene saltato, non fallito.
    config()->set('backup.key', 'present');
    expect($event->filtersPass($this->app))->toBeTrue();

    config()->set('backup.key', null);
    expect($event->filtersPass($this->app))->toBeFalse();
});
