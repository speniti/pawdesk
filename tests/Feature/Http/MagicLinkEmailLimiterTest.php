<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('the magic-link-email limiter throttles by target email across different ips', function () {
    Route::post('/_testing/send-magic-link', fn () => response()->noContent())->middleware('throttle:magic-link-email');

    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$attempt}"])
            ->post('/_testing/send-magic-link', ['email' => 'victim@example.com'])
            ->assertSuccessful();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
        ->post('/_testing/send-magic-link', ['email' => 'victim@example.com'])
        ->assertTooManyRequests();

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.2'])
        ->post('/_testing/send-magic-link', ['email' => 'other@example.com'])
        ->assertSuccessful();
});

test('the magic-link-email limiter falls back to the ip key when no email is provided', function () {
    Route::post('/_testing/send-magic-link', fn () => response()->noContent())->middleware('throttle:magic-link-email');

    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->post('/_testing/send-magic-link')
            ->assertSuccessful();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
        ->post('/_testing/send-magic-link')
        ->assertTooManyRequests();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.51'])
        ->post('/_testing/send-magic-link')
        ->assertSuccessful();
});
