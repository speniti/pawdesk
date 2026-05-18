<?php

declare(strict_types=1);

use App\Filament\Pages\Tenancy\EditTenantSettings;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

describe('authorization', function () {
    test('admin can access settings page', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->assertOk();
    });

    test('staff cannot access settings page', function () {
        $staff = User::factory()->create();
        $staff->tenants()->attach($this->tenant);

        bootFilamentPanelAs($staff, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->assertStatus(404);
    });

    test('admin from another tenant cannot access settings page', function () {
        $otherTenant = Tenant::factory()->create();
        $otherAdmin = User::factory()->admin()->create();
        $otherAdmin->tenants()->attach($otherTenant);

        bootFilamentPanelAs($otherAdmin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->assertStatus(404);
    });
});

describe('form rendering', function () {
    test('settings page loads with tenant data', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->assertOk()
            ->assertFormSet([
                'settings' => [
                    'slot_duration_minutes' => 30,
                    'buffer_minutes' => 15,
                ],
            ]);
    });
});

describe('saving opening hours', function () {
    test('can save opening hours for each day', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        $openingHours = [
            'monday' => [['open' => '08:00', 'close' => '19:00']],
            'tuesday' => [['open' => '08:00', 'close' => '19:00']],
            'wednesday' => [['open' => '08:00', 'close' => '19:00']],
            'thursday' => [['open' => '08:00', 'close' => '19:00']],
            'friday' => [['open' => '08:00', 'close' => '19:00']],
            'saturday' => [['open' => '09:00', 'close' => '13:00']],
            'sunday' => [],
        ];

        Livewire::test(EditTenantSettings::class)
            ->fillForm(['opening_hours' => $openingHours])
            ->call('save')
            ->assertNotified();

        $this->tenant->refresh();

        expect($this->tenant->opening_hours)->toBe($openingHours);
    });

    test('can save multiple time ranges per day', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        $openingHours = [
            'monday' => [
                ['open' => '09:00', 'close' => '13:00'],
                ['open' => '14:30', 'close' => '19:00'],
            ],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        Livewire::test(EditTenantSettings::class)
            ->fillForm(['opening_hours' => $openingHours])
            ->call('save')
            ->assertNotified();

        $this->tenant->refresh();

        expect($this->tenant->opening_hours['monday'])->toHaveCount(2)
            ->and($this->tenant->opening_hours['monday'][0])->toBe(['open' => '09:00', 'close' => '13:00'])
            ->and($this->tenant->opening_hours['monday'][1])->toBe(['open' => '14:30', 'close' => '19:00']);
    });

    test('close time must be after open time', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        $openingHours = [
            'monday' => [['open' => '18:00', 'close' => '09:00']],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        Livewire::test(EditTenantSettings::class)
            ->fillForm(['opening_hours' => $openingHours])
            ->call('save')
            ->assertHasFormErrors();
    });

    test('empty day means closed', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        $openingHours = [
            'monday' => [['open' => '09:00', 'close' => '18:00']],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];

        Livewire::test(EditTenantSettings::class)
            ->fillForm(['opening_hours' => $openingHours])
            ->call('save')
            ->assertNotified();

        $this->tenant->refresh();

        expect($this->tenant->opening_hours['tuesday'])->toBe([])
            ->and($this->tenant->opening_hours['sunday'])->toBe([]);
    });
});

describe('saving slot settings', function () {
    test('can save slot duration and buffer', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->fillForm([
                'settings' => [
                    'slot_duration_minutes' => 45,
                    'buffer_minutes' => 10,
                ],
            ])
            ->call('save')
            ->assertNotified();

        $this->tenant->refresh();

        expect($this->tenant->settings['slot_duration_minutes'])->toBe(45)
            ->and($this->tenant->settings['buffer_minutes'])->toBe(10);
    });

    test('slot duration must be positive', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->fillForm([
                'settings' => [
                    'slot_duration_minutes' => 0,
                    'buffer_minutes' => 15,
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['settings.slot_duration_minutes']);
    });

    test('buffer must be zero or positive', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->fillForm([
                'settings' => [
                    'slot_duration_minutes' => 30,
                    'buffer_minutes' => -5,
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['settings.buffer_minutes']);
    });
});

describe('saving notification credentials', function () {
    test('can save notification credentials', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(EditTenantSettings::class)
            ->fillForm([
                'notification_settings' => [
                    'mailgun_api_key' => 'key-test-mailgun-123',
                    'mailgun_domain' => 'mg.example.com',
                    'vonage_api_key' => 'key-test-vonage-456',
                    'vonage_api_secret' => 'secret-test-vonage-789',
                    'vonage_sms_sender_id' => '+39123456789',
                ],
            ])
            ->call('save')
            ->assertNotified();

        $this->tenant->refresh();

        expect($this->tenant->notification_settings)->toBe([
            'mailgun_api_key' => 'key-test-mailgun-123',
            'mailgun_domain' => 'mg.example.com',
            'vonage_api_key' => 'key-test-vonage-456',
            'vonage_api_secret' => 'secret-test-vonage-789',
            'vonage_sms_sender_id' => '+39123456789',
        ]);
    });
});
