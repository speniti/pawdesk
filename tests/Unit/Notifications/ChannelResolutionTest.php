<?php

declare(strict_types=1);

use App\Enums\PreferredChannel;
use App\Models\Appointment;
use App\Models\Customer;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\AppointmentRequestedNotification;
use App\Notifications\Channels\TenantMailChannel;

test('via returns TenantMailChannel for email preferred channel', function () {
    $customer = new Customer(['preferred_channel' => PreferredChannel::Email]);
    $appointment = new Appointment(['tenant_id' => 1, 'customer_id' => 1]);
    $appointment->id = 1;

    $notification = new AppointmentConfirmedNotification($appointment);

    expect($notification->via($customer))->toBe([TenantMailChannel::class]);
});

test('via returns TenantMailChannel for sms preferred channel as fallback', function () {
    $customer = new Customer(['preferred_channel' => PreferredChannel::Sms]);
    $appointment = new Appointment(['tenant_id' => 1, 'customer_id' => 1]);
    $appointment->id = 1;

    $notification = new AppointmentConfirmedNotification($appointment);

    // TODO: update when SMS channel is active
    expect($notification->via($customer))->toBe([TenantMailChannel::class]);
});

test('via returns TenantMailChannel for whatsapp preferred channel as fallback', function () {
    $customer = new Customer(['preferred_channel' => PreferredChannel::Whatsapp]);
    $appointment = new Appointment(['tenant_id' => 1, 'customer_id' => 1]);
    $appointment->id = 1;

    $notification = new AppointmentConfirmedNotification($appointment);

    // TODO: update when WhatsApp channel is active
    expect($notification->via($customer))->toBe([TenantMailChannel::class]);
});

test('via returns TenantMailChannel when preferred channel is null', function () {
    $customer = new Customer(['preferred_channel' => null]);
    $appointment = new Appointment(['tenant_id' => 1, 'customer_id' => 1]);
    $appointment->id = 1;

    $notification = new AppointmentRequestedNotification($appointment);

    expect($notification->via($customer))->toBe([TenantMailChannel::class]);
});
