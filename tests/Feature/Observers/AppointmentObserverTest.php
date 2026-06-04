<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Notifications\AppointmentCancelledNotification;
use App\Notifications\AppointmentCompletedNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentRequestedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('created dispatches AppointmentRequestedNotification', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
    ]);

    Notification::assertSentTo(
        $customer,
        AppointmentRequestedNotification::class,
        function (AppointmentRequestedNotification $notification) use ($appointment) {
            return $notification->notificationType() === 'appointment_requested'
                && $notification->getAppointment()->id === $appointment->id;
        },
    );
});

test('status change to Confirmed sends AppointmentConfirmedNotification', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
        'status' => AppointmentStatus::Requested->value,
    ]);

    Notification::fake();

    $appointment->update(['status' => AppointmentStatus::Confirmed->value]);

    Notification::assertSentTo($customer, AppointmentConfirmedNotification::class);
    Notification::assertNotSentTo($customer, AppointmentCancelledNotification::class);
});

test('status change to Cancelled sends AppointmentCancelledNotification', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
        'status' => AppointmentStatus::Requested->value,
    ]);

    Notification::fake();

    $appointment->update(['status' => AppointmentStatus::Cancelled->value]);

    Notification::assertSentTo($customer, AppointmentCancelledNotification::class);
});

test('status change to Completed sends AppointmentCompletedNotification', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
        'status' => AppointmentStatus::Confirmed->value,
    ]);

    Notification::fake();

    $appointment->update(['status' => AppointmentStatus::InProgress->value]);
    Notification::fake();

    $appointment->update(['status' => AppointmentStatus::Completed->value]);

    Notification::assertSentTo($customer, AppointmentCompletedNotification::class);
});

test('status change to InProgress sends no notification', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
        'status' => AppointmentStatus::Confirmed->value,
    ]);

    Notification::fake();

    $appointment->update(['status' => AppointmentStatus::InProgress->value]);

    Notification::assertNothingSent();
});

test('status change to NoShow sends no notification', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
        'status' => AppointmentStatus::Confirmed->value,
    ]);

    Notification::fake();

    $appointment->update(['status' => AppointmentStatus::NoShow->value]);

    Notification::assertNothingSent();
});

test('non-status update sends no notification', function () {
    $customer = Customer::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'tenant_id' => $customer->tenant_id,
        'status' => AppointmentStatus::Requested->value,
    ]);

    Notification::fake();

    $appointment->update(['internal_notes' => 'some notes']);

    Notification::assertNothingSent();
});
