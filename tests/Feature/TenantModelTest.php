<?php

declare(strict_types=1);

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
    expect($tenant->slug)->toBe(Illuminate\Support\Str::slug($tenant->name));
    expect($tenant->opening_hours)->toBeArray();
    expect($tenant->settings)->toBeArray();
});

test('Tenant encrypts and decrypts notification_settings', function () {
    $settings = ['mailgun_api_key' => 'key-test123'];
    $tenant = Tenant::factory()->create(['notification_settings' => $settings]);
    $tenant->refresh();

    expect($tenant->notification_settings)->toBe($settings);
});

test('Tenant has many customers', function () {
    $tenant = Tenant::factory()->create();
    \App\Models\Customer::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    expect($tenant->customers)->toHaveCount(2);
});

test('Tenant has many pets', function () {
    $tenant = Tenant::factory()->create();
    \App\Models\Customer::factory()->create(['tenant_id' => $tenant->id]);
    \App\Models\Pet::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    expect($tenant->pets)->toHaveCount(2);
});

test('Tenant has many services', function () {
    $tenant = Tenant::factory()->create();
    \App\Models\Service::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    expect($tenant->services)->toHaveCount(2);
});

test('Tenant has many appointments', function () {
    $tenant = Tenant::factory()->create();
    \App\Models\Appointment::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    expect($tenant->appointments)->toHaveCount(2);
});

test('Tenant has many treatments', function () {
    $tenant = Tenant::factory()->create();
    $appointment = \App\Models\Appointment::factory()->create(['tenant_id' => $tenant->id]);
    \App\Models\Treatment::factory()->count(2)->forAppointment($appointment)->create();

    expect($tenant->treatments)->toHaveCount(2);
});
