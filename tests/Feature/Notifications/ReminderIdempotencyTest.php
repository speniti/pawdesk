<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function reminderTestAppointment(): Appointment
{
    return Appointment::factory()->create([
        'status' => AppointmentStatus::Confirmed->value,
        'start_time' => now()->addHours(23),
        'end_time' => now()->addHours(24),
    ]);
}

test('wasDispatched is true for sent or pending logs of the given type', function () {
    $appointment = reminderTestAppointment();

    expect(NotificationLog::wasDispatched($appointment->id, 'appointment_reminder_24h'))->toBeFalse();

    $appointment->notificationLogs()->create([
        'tenant_id' => $appointment->tenant_id,
        'customer_id' => $appointment->customer_id,
        'type' => 'appointment_reminder_24h',
        'channel' => 'mail',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    expect(NotificationLog::wasDispatched($appointment->id, 'appointment_reminder_24h'))->toBeTrue()
        ->and(NotificationLog::wasDispatched($appointment->id, 'appointment_reminder_1h'))->toBeFalse();
});

test('pending logs count as dispatched', function () {
    $appointment = reminderTestAppointment();

    $appointment->notificationLogs()->create([
        'tenant_id' => $appointment->tenant_id,
        'customer_id' => $appointment->customer_id,
        'type' => 'appointment_reminder_1h',
        'channel' => 'sms',
    ]);

    expect(NotificationLog::wasDispatched($appointment->id, 'appointment_reminder_1h'))->toBeTrue();
});

test('failed logs do not count as dispatched', function () {
    $appointment = reminderTestAppointment();

    $appointment->notificationLogs()->create([
        'tenant_id' => $appointment->tenant_id,
        'customer_id' => $appointment->customer_id,
        'type' => 'appointment_reminder_24h',
        'channel' => 'mail',
        'status' => 'failed',
        'error_message' => 'boom',
        'failed_at' => now(),
    ]);

    expect(NotificationLog::wasDispatched($appointment->id, 'appointment_reminder_24h'))->toBeFalse();
});
