<?php

declare(strict_types=1);

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();

    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

function sendPrivacyPolicy(Customer $customer, User $user, Tenant $tenant)
{
    bootFilamentPanelAs($user, $tenant);

    return Livewire::test(EditCustomer::class, ['record' => $customer->id])
        ->callAction('send-privacy-policy');
}

test('azione invia l\'informativa e aggiorna gdpr_policy_sent_at', function () {
    $tenant = Tenant::factory()->create([
        'notification_settings' => [
            'mailgun_api_key' => 'key-test-123',
            'mailgun_domain' => 'example.com',
        ],
    ]);
    $admin = User::factory()->admin()->create();
    $admin->tenants()->attach($tenant);
    $customer = Customer::factory()->for($tenant)->create(['gdpr_policy_sent_at' => null]);

    sendPrivacyPolicy($customer, $admin, $tenant)->assertNotified();

    $customer->refresh();

    expect($customer->gdpr_policy_sent_at)->not->toBeNull();

    $log = NotificationLog::query()->where('customer_id', $customer->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->type)->toBe('privacy_policy')
        ->and($log->channel)->toBe('mail')
        ->and($log->appointment_id)->toBeNull();
});

test('azione non invia e non aggiorna quando il cliente non ha email', function () {
    $tenant = Tenant::factory()->create([
        'notification_settings' => [
            'mailgun_api_key' => 'key-test-123',
            'mailgun_domain' => 'example.com',
        ],
    ]);
    $admin = User::factory()->admin()->create();
    $admin->tenants()->attach($tenant);
    $customer = Customer::factory()->for($tenant)->create([
        'email' => '',
        'gdpr_policy_sent_at' => null,
    ]);

    sendPrivacyPolicy($customer, $admin, $tenant)->assertNotified();

    $customer->refresh();

    expect($customer->gdpr_policy_sent_at)->toBeNull()
        ->and(NotificationLog::query()->where('customer_id', $customer->id)->exists())->toBeFalse();
});

test('azione non invia quando Mailgun non è configurato per il salone', function () {
    $tenant = Tenant::factory()->create(['notification_settings' => []]);
    $admin = User::factory()->admin()->create();
    $admin->tenants()->attach($tenant);
    $customer = Customer::factory()->for($tenant)->create(['gdpr_policy_sent_at' => null]);

    sendPrivacyPolicy($customer, $admin, $tenant)->assertNotified();

    $customer->refresh();

    expect($customer->gdpr_policy_sent_at)->toBeNull()
        ->and(NotificationLog::query()->where('customer_id', $customer->id)->exists())->toBeFalse();
});

test('in ambiente locale l\'azione invia anche senza Mailgun configurato', function () {
    app()->detectEnvironment(fn () => 'local');

    $tenant = Tenant::factory()->create(['notification_settings' => []]);
    $admin = User::factory()->admin()->create();
    $admin->tenants()->attach($tenant);
    $customer = Customer::factory()->for($tenant)->create(['gdpr_policy_sent_at' => null]);

    sendPrivacyPolicy($customer, $admin, $tenant)->assertNotified();

    $customer->refresh();

    expect($customer->gdpr_policy_sent_at)->not->toBeNull();

    $log = NotificationLog::query()->where('customer_id', $customer->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->type)->toBe('privacy_policy');
});
