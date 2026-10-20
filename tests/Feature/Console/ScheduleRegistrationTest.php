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
