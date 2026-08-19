<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function confirmedAppointment(array $attributes = []): Appointment
{
    return Appointment::factory()->create([
        'status' => AppointmentStatus::Confirmed->value,
        'start_time' => now()->addHours(23),
        'end_time' => now()->addHours(24),
        ...$attributes,
    ]);
}

test('sends 24h and 1h reminders for confirmed appointments in window', function () {
    $in24hWindow = confirmedAppointment();
    $in1hWindow = confirmedAppointment([
        'start_time' => now()->addMinutes(30),
        'end_time' => now()->addMinutes(90),
    ]);

    Notification::fake();

    $this->artisan('appointments:send-reminders')->assertSuccessful();

    Notification::assertSentTo(
        $in24hWindow->customer,
        AppointmentReminderNotification::class,
        fn (AppointmentReminderNotification $notification) => $notification->notificationType() === 'appointment_reminder_24h',
    );
    Notification::assertSentTo(
        $in1hWindow->customer,
        AppointmentReminderNotification::class,
        fn (AppointmentReminderNotification $notification) => $notification->notificationType() === 'appointment_reminder_1h',
    );

    expect(NotificationLog::where('type', 'appointment_reminder_24h')->where('status', 'pending')->count())->toBe(1)
        ->and(NotificationLog::where('type', 'appointment_reminder_1h')->where('status', 'pending')->count())->toBe(1);
});

test('does not send reminders for appointments outside the window', function () {
    confirmedAppointment([ // more than 24h away
        'start_time' => now()->addDays(3),
        'end_time' => now()->addDays(3)->addHours(1),
    ]);
    confirmedAppointment([ // already started
        'start_time' => now()->subHours(1),
        'end_time' => now()->subMinutes(30),
    ]);
    Appointment::factory()->create([ // in window but not confirmed
        'status' => AppointmentStatus::Requested->value,
        'start_time' => now()->addHours(10),
        'end_time' => now()->addHours(11),
    ]);

    Notification::fake();

    $this->artisan('appointments:send-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

test('second run skips appointments whose reminder was already dispatched', function () {
    $appointment = confirmedAppointment();

    Notification::fake();

    $this->artisan('appointments:send-reminders')->assertSuccessful();
    $this->artisan('appointments:send-reminders')->assertSuccessful();

    Notification::assertSentTo($appointment->customer, AppointmentReminderNotification::class, 1);
});

test('sends again a reminder whose previous attempt failed', function () {
    $appointment = confirmedAppointment();

    $appointment->notificationLogs()->create([
        'tenant_id' => $appointment->tenant_id,
        'customer_id' => $appointment->customer_id,
        'type' => 'appointment_reminder_24h',
        'channel' => 'mail',
        'status' => 'failed',
        'error_message' => 'boom',
        'failed_at' => now(),
    ]);

    Notification::fake();

    $this->artisan('appointments:send-reminders')->assertSuccessful();

    Notification::assertSentTo($appointment->customer, AppointmentReminderNotification::class);
});
