<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;

test('database connection is sqlite in testing', function () {
    expect(config('database.default'))->toBe('sqlite');
});

test('app timezone is Europe/Rome', function () {
    expect(config('app.timezone'))->toBe('Europe/Rome');
});

test('app cipher is AES-256-CBC', function () {
    expect(config('app.cipher'))->toBe('AES-256-CBC');
});

test('required service providers are registered', function () {
    $loadedProviders = collect(app()->getLoadedProviders());

    expect($loadedProviders->has(AppServiceProvider::class))->toBeTrue();
    expect($loadedProviders->has(AdminPanelProvider::class))->toBeTrue();
});

test('Filament admin panel is configured', function () {
    $panel = Filament::getPanel('admin');

    expect($panel)->not->toBeNull();
    expect($panel->getId())->toBe('admin');
    expect($panel->getPath())->toBe('admin');
});
