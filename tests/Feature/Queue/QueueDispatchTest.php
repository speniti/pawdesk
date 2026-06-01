<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

describe('queue dispatch', function () {
    test('closure job is dispatched to the queue', function () {
        Queue::fake();

        dispatch(fn () => null);

        Queue::assertClosurePushed();
    });

    test('job is not dispatched when not called', function () {
        Queue::fake();

        Queue::assertClosureNotPushed();
        Queue::assertNothingPushed();
    });
});

describe('queue processing', function () {
    test('closure job is processed via sync driver', function () {
        Log::shouldReceive('info')->once()->with('Queue works!');

        dispatch(fn () => Log::info('Queue works!'));
    });
});
