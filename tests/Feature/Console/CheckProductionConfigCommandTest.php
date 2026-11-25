<?php

declare(strict_types=1);

test('fails when debug mode is enabled in production', function () {
    $this->app->instance('env', 'production');
    config(['app.debug' => true]);

    $this->artisan('app:check-production-config')
        ->assertFailed()
        ->expectsOutputToContain('APP_DEBUG');
});

test('passes when debug mode is disabled in production', function () {
    $this->app->instance('env', 'production');
    config(['app.debug' => false]);

    $this->artisan('app:check-production-config')->assertSuccessful();
});

test('passes when debug mode is enabled outside production', function () {
    $this->app->instance('env', 'local');
    config(['app.debug' => true]);

    $this->artisan('app:check-production-config')->assertSuccessful();
});
