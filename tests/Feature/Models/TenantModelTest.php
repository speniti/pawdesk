<?php

declare(strict_types=1);

use App\Models\Tenant;

describe('notification_settings encryption', function () {
    test('can encrypt and decrypt notification_settings', function () {
        $settings = ['mailgun_api_key' => 'key-test123'];
        $tenant = Tenant::factory()->create(['notification_settings' => $settings]);

        expect($tenant->notification_settings)->toBe($settings);
    });

    test('does not store notification_settings plaintext in the database', function () {
        $secret = 'plaintext-secret-value-abc123';
        $tenant = Tenant::factory()->create(['notification_settings' => ['vonage_api_secret' => $secret]]);

        expect($tenant->getRawOriginal('notification_settings'))->not->toContain($secret);
    });
});
