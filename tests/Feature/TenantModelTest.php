<?php

use App\Models\Tenant;

test('Tenant has correct fillable attributes', function () {
    $tenant = new Tenant;

    expect($tenant->getFillable())->toBe([
        'name',
        'slug',
        'primary_color',
        'opening_hours',
        'notification_settings',
        'settings',
    ]);
});

test('Tenant casts opening_hours and settings as array', function () {
    $tenant = new Tenant;
    $casts = $tenant->getCasts();

    expect($casts)->toHaveKey('opening_hours', 'array');
    expect($casts)->toHaveKey('settings', 'array');
});

test('Tenant casts notification_settings as encrypted:array', function () {
    $tenant = new Tenant;
    $casts = $tenant->getCasts();

    expect($casts)->toHaveKey('notification_settings', 'encrypted:array');
});

test('Tenant factory creates valid tenant', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->name)->not->toBeEmpty();
    expect($tenant->slug)->not->toBeEmpty();
    expect($tenant->slug)->toBe(\Illuminate\Support\Str::slug($tenant->name));
    expect($tenant->opening_hours)->toBeArray();
    expect($tenant->settings)->toBeArray();
});

test('Tenant encrypts and decrypts notification_settings', function () {
    $settings = ['mailgun_api_key' => 'key-test123'];
    $tenant = Tenant::factory()->create(['notification_settings' => $settings]);
    $tenant->refresh();

    expect($tenant->notification_settings)->toBe($settings);
});
