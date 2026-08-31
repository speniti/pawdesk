<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;

beforeEach(function () {
    Notification::fake();
});

test('24h reminder has its own notification type and Italian content', function () {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Confirmed->value,
        'start_time' => now()->addDay()->setTime(15, 0),
        'end_time' => now()->addDay()->setTime(17, 0),
    ]);
    $customer = $appointment->customer;

    $notification = new AppointmentReminderNotification($appointment, 24);

    expect($notification->notificationType())->toBe('appointment_reminder_24h')
        ->and($notification->smsContent())->toContain('domani')
        ->and($notification->getAppointment()->id)->toBe($appointment->id);

    $mail = $notification->toMail($customer);
    expect($mail->subject)->toBe('Promemoria appuntamento');
});

test('1h reminder uses the 1h type and a today label', function () {
    $this->travelTo(now()->setTime(10, 0));

    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Confirmed->value,
        'start_time' => now()->addMinutes(50),
        'end_time' => now()->addMinutes(110),
    ]);

    $notification = new AppointmentReminderNotification($appointment, 1);

    expect($notification->notificationType())->toBe('appointment_reminder_1h')
        ->and($notification->smsContent())->toContain('oggi');
});
