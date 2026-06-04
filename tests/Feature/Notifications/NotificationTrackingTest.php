<?php

declare(strict_types=1);

use App\Enums\NotificationStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Notifications\AppointmentConfirmedNotification;

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
