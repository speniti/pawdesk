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

describe('privacy data accessors', function () {
    test('business name falls back to tenant name when not set', function () {
        $tenant = Tenant::factory()->create(['name' => 'Salone Ace']);

        expect($tenant->privacyBusinessName())->toBe('Salone Ace');
    });

    test('business name uses the privacy setting when set', function () {
        $tenant = Tenant::factory()->create([
            'name' => 'Salone Ace',
            'settings' => ['privacy_business_name' => 'Ace S.r.l.'],
        ]);

        expect($tenant->privacyBusinessName())->toBe('Ace S.r.l.');
    });

    test('contact email falls back to the mail from address', function () {
        $tenant = Tenant::factory()->create([
            'notification_settings' => ['mail_from_address' => 'noreply@saloneace.it'],
        ]);

        expect($tenant->privacyContactEmail())->toBe('noreply@saloneace.it');
    });

    test('privacy contact email takes precedence over the mail from address', function () {
        $tenant = Tenant::factory()->create([
            'notification_settings' => ['mail_from_address' => 'noreply@saloneace.it'],
            'settings' => ['privacy_contact_email' => 'privacy@saloneace.it'],
        ]);

        expect($tenant->privacyContactEmail())->toBe('privacy@saloneace.it');
    });

    test('optional fields return null when not set', function () {
        $tenant = Tenant::factory()->create();

        expect($tenant->privacyOwnerName())->toBeNull()
            ->and($tenant->privacyVatNumber())->toBeNull()
            ->and($tenant->privacyBusinessAddress())->toBeNull()
            ->and($tenant->privacyContactEmail())->toBeNull()
            ->and($tenant->privacyContactPhone())->toBeNull();
    });
});
