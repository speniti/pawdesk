<?php

declare(strict_types=1);

use App\Enums\NotificationStatus;
use App\Enums\PreferredChannel;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\Channels\TenantVonageChannel;
use Illuminate\Support\Facades\Notification;

test('log is created as pending when notification is dispatched', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $log = $notification->createLog($customer);

    expect($log)->toBeInstanceOf(NotificationLog::class)
        ->and($log->status)->toBe(NotificationStatus::Pending)
        ->and($log->appointment_id)->toBe($appointment->id)
        ->and($log->customer_id)->toBe($customer->id)
        ->and($log->type)->toBe('appointment_confirmed')
        ->and($log->channel)->toBe('mail');
});

test('log transitions to sent with sent_at timestamp', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $log = $notification->createLog($customer);

    $notification->afterSending($customer, 'mail', null);

    $log->refresh();

    expect($log->status)->toBe(NotificationStatus::Sent)
        ->and($log->sent_at)->not->toBeNull();
});

test('log transitions to failed with error message', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $log = $notification->createLog($customer);

    $exception = new RuntimeException('SMTP connection failed');
    $notification->failed($exception);

    $log->refresh();

    expect($log->status)->toBe(NotificationStatus::Failed)
        ->and($log->error_message)->toBe('SMTP connection failed')
        ->and($log->failed_at)->not->toBeNull();
});

test('sms log transitions to sent when sent via the vonage channel', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create([
        'notification_settings' => [
            'vonage_api_key' => 'key',
            'vonage_api_secret' => 'secret',
        ],
    ]);
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '+393331234567',
    ]);
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $log = $notification->createLog($customer);

    expect($log->channel)->toBe('sms');

    $notification->afterSending($customer, TenantVonageChannel::class, null);

    $log->refresh();

    expect($log->status)->toBe(NotificationStatus::Sent)
        ->and($log->sent_at)->not->toBeNull();
});

test('sms log transitions to failed when the vonage send fails', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create([
        'notification_settings' => [
            'vonage_api_key' => 'key',
            'vonage_api_secret' => 'secret',
        ],
    ]);
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '+393331234567',
    ]);
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $log = $notification->createLog($customer);

    $notification->failed(new RuntimeException('Vonage SMS error: Throttled'));

    $log->refresh();

    expect($log->status)->toBe(NotificationStatus::Failed)
        ->and($log->error_message)->toBe('Vonage SMS error: Throttled')
        ->and($log->failed_at)->not->toBeNull();
});
