<?php

declare(strict_types=1);

use App\Enums\NotificationStatus;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Notifications\Channels\TenantMailChannel;
use App\Notifications\PrivacyPolicyNotification;
use Illuminate\Mail\Markdown;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('email contiene il link all\'informativa e il nome del cliente', function () {
    $tenant = Tenant::factory()->create(['name' => 'Salone Ace', 'slug' => 'salone-ace']);
    $customer = Customer::factory()->for($tenant)->create([
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
    ]);

    $notification = new PrivacyPolicyNotification($tenant);
    $message = $notification->toMail($customer);

    $rendered = (string) $message->render();

    expect($message->subject)->toBe('Informativa sulla privacy — Salone Ace')
        ->and($rendered)->toContain('Mario Rossi')
        ->toContain('/salone-ace/privacy-policy');
});

test('log creato come pending senza appuntamento associato', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->for($tenant)->create();

    $notification = new PrivacyPolicyNotification($tenant);
    $log = $notification->createLog($customer);

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->status)->toBe(NotificationStatus::Pending)
        ->and($log->appointment_id)->toBeNull()
        ->and($log->customer_id)->toBe($customer->id)
        ->and($log->tenant_id)->toBe($tenant->id)
        ->and($log->type)->toBe('privacy_policy')
        ->and($log->channel)->toBe('mail');
});

test('il canale segna il log skipped quando il tenant non ha Mailgun configurato', function () {
    $tenant = Tenant::factory()->create(['notification_settings' => []]);
    $customer = Customer::factory()->for($tenant)->create();

    $notification = new PrivacyPolicyNotification($tenant);
    $notification->createLog($customer);

    $channel = app(TenantMailChannel::class);

    expect($channel->send($customer, $notification))->toBeNull();

    Mail::assertNothingSent();

    $log = NotificationLog::query()->where('customer_id', $customer->id)->first();
    expect($log->status)->toBe(NotificationStatus::Skipped)
        ->and($log->error_message)->toBe('Mailgun non configurato per il tenant.');
});

test('in ambiente locale il canale invia via mailer di default anche senza Mailgun', function () {
    app()->detectEnvironment(fn () => 'local');

    // Mail::fake() non intercetta l'invio a livello di canale (view non-Mailable):
    // usa il manager reale, come in NotificationTrackingTest.
    $realManager = Mail::getFacadeRoot()->manager;
    $channel = new TenantMailChannel($realManager, app(Markdown::class));

    $tenant = Tenant::factory()->create(['notification_settings' => []]);
    $customer = Customer::factory()->for($tenant)->create();

    $notification = new PrivacyPolicyNotification($tenant);
    $notification->createLog($customer);

    // MAIL_MAILER=array: un invio riuscito restituisce un SentMessage
    expect($channel->send($customer, $notification))->toBeInstanceOf(SentMessage::class);

    $log = NotificationLog::query()->where('customer_id', $customer->id)->first();
    expect($log->status)->not->toBe(NotificationStatus::Skipped);
});

test('il canale segna il log skipped quando il cliente non ha email', function () {
    $tenant = Tenant::factory()->create([
        'notification_settings' => [
            'mailgun_api_key' => 'key-test-123',
            'mailgun_domain' => 'example.com',
        ],
    ]);
    $customer = Customer::factory()->for($tenant)->create(['email' => '']);

    $notification = new PrivacyPolicyNotification($tenant);
    $notification->createLog($customer);

    $channel = app(TenantMailChannel::class);

    expect($channel->send($customer, $notification))->toBeNull();

    Mail::assertNothingSent();

    $log = NotificationLog::query()->where('customer_id', $customer->id)->first();
    expect($log->status)->toBe(NotificationStatus::Skipped)
        ->and($log->error_message)->toBe('Email del cliente mancante.');
});

test('log diventa sent con sent_at dopo l\'invio', function () {
    $tenant = Tenant::factory()->create();
    $customer = Customer::factory()->for($tenant)->create();

    $notification = new PrivacyPolicyNotification($tenant);
    $log = $notification->createLog($customer);

    $notification->afterSending($customer, TenantMailChannel::class, true);

    $log->refresh();

    expect($log->status)->toBe(NotificationStatus::Sent)
        ->and($log->sent_at)->not->toBeNull();
});
