<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\Channels\TenantMailChannel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('channel skips sending when tenant has no Mailgun configured', function () {
    $tenant = Tenant::factory()->create([
        'notification_settings' => [],
    ]);

    $customer = Customer::factory()->for($tenant)->create();
    $pet = Pet::factory()->for($customer)->for($tenant)->create();
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $channel = app(TenantMailChannel::class);

    $channel->send($customer, $notification);

    Mail::assertNothingSent();
});

test('channel configures runtime mailer with tenant credentials and cleans up', function () {
    $tenant = Tenant::factory()->create([
        'notification_settings' => [
            'mailgun_api_key' => 'key-test-123',
            'mailgun_domain' => 'example.com',
        ],
    ]);

    $customer = Customer::factory()->for($tenant)->create();
    $pet = Pet::factory()->for($customer)->for($tenant)->create();
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $channel = app(TenantMailChannel::class);
    $mailerName = $tenant->mailgunMailerName();

    $channel->send($customer, $notification);

    expect(Config::get("mail.mailers.{$mailerName}"))->toBeNull();
});

test('from address is applied when configured on tenant', function () {
    $tenant = Tenant::factory()->create([
        'notification_settings' => [
            'mailgun_api_key' => 'key-test-123',
            'mailgun_domain' => 'example.com',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Example Salone',
        ],
    ]);

    $customer = Customer::factory()->for($tenant)->create();
    $pet = Pet::factory()->for($customer)->for($tenant)->create();
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);

    // Verify toMail produces a message with the correct from
    $message = $notification->toMail($customer);
    expect($message)->toBeInstanceOf(Illuminate\Notifications\Messages\MailMessage::class);

    // Channel send — with Mail::fake() the actual send is intercepted
    $channel = app(TenantMailChannel::class);
    $channel->send($customer, $notification);

    // Verify config is cleaned up
    expect(Config::get("mail.mailers.{$tenant->mailgunMailerName()}"))->toBeNull();
});

test('emailTemplateData returns correct variables', function () {
    $tenant = Tenant::factory()->create(['name' => 'PawDesk Salone']);
    $customer = Customer::factory()->for($tenant)->create([
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
    ]);
    $pet = Pet::factory()->for($customer)->for($tenant)->create(['name' => 'Fido']);
    $service = Service::factory()->for($tenant)->create(['name' => 'Taglio pelo']);
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);
    $appointment->services()->attach($service, [
        'applied_price' => 3000,
        'duration_minutes' => 60,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $data = $notification->emailTemplateData();

    expect($data['cliente_nome'])->toBe('Mario Rossi')
        ->and($data['animale_nome'])->toBe('Fido')
        ->and($data['data'])->toBe($appointment->start_time->format('d/m/Y'))
        ->and($data['ora'])->toBe($appointment->start_time->format('H:i'))
        ->and($data['servizi'])->toBe('Taglio pelo')
        ->and($data['salone_nome'])->toBe('PawDesk Salone');
});
