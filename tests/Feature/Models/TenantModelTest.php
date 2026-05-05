<?php

declare(strict_types=1);

use App\Models\Tenant;

test('Tenant encrypts and decrypts notification_settings', function () {
    $settings = ['mailgun_api_key' => 'key-test123'];
    $tenant = Tenant::factory()->create(['notification_settings' => $settings]);
    $tenant->refresh();

    expect($tenant->notification_settings)->toBe($settings);
});
