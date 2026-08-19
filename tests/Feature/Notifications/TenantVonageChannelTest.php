<?php

declare(strict_types=1);

use App\Enums\NotificationStatus;
use App\Enums\PreferredChannel;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Tenant;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\Channels\TenantVonageChannel;
use App\Services\VonageSmsSender;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    // Suppress the AppointmentObserver dispatching real sends on factory create().
    Notification::fake();
});

function vonageTenant(array $overrides = []): Tenant
{
    return Tenant::factory()->create(array_merge([
        'notification_settings' => [
            'vonage_api_key' => 'vonage-key',
            'vonage_api_secret' => 'vonage-secret',
            'vonage_sms_sender_id' => 'PawDesk',
        ],
    ], $overrides));
}

test('via routes to TenantVonageChannel when customer prefers sms and tenant has vonage configured', function () {
    $tenant = vonageTenant();
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '+393331234567',
    ]);
    $pet = Pet::factory()->for($customer)->for($tenant)->create();
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);

    expect($notification->via($customer))->toBe([TenantVonageChannel::class]);
});

test('via falls back to TenantMailChannel when tenant has no vonage configured', function () {
    $tenant = vonageTenant(['notification_settings' => []]);
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '+393331234567',
    ]);

    expect((new AppointmentConfirmedNotification(
        Appointment::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id])
    ))->via($customer))->toBe([App\Notifications\Channels\TenantMailChannel::class]);
});

test('channel skips sending and marks the log skipped when tenant has no vonage configured', function () {
    // Vonage is configured when the log is created, then removed before the
    // queued send runs: the sms log must be marked skipped, not left pending.
    $tenant = vonageTenant();
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '+393331234567',
    ]);
    $pet = Pet::factory()->for($customer)->for($tenant)->create();
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $notification->createLog($customer);

    $tenant->update(['notification_settings' => []]);

    $this->mock(VonageSmsSender::class, fn ($mock) => $mock->shouldNotReceive('send'));

    $channel = app(TenantVonageChannel::class);
    expect($channel->send($customer, $notification))->toBeNull();

    $log = NotificationLog::query()->where('appointment_id', $appointment->id)->first();
    expect($log->status)->toBe(NotificationStatus::Skipped)
        ->and($log->error_message)->toBe('Vonage non configurato per il tenant.');
});

test('channel marks the log failed and skips sending when phone is not valid E.164', function () {
    $tenant = vonageTenant();
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '333 1234', // not E.164
    ]);
    $pet = Pet::factory()->for($customer)->for($tenant)->create();
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);

    $notification = new AppointmentConfirmedNotification($appointment);
    $notification->createLog($customer);

    $this->mock(VonageSmsSender::class, fn ($mock) => $mock->shouldNotReceive('send'));

    $channel = app(TenantVonageChannel::class);
    $channel->send($customer, $notification);

    $log = NotificationLog::query()->where('appointment_id', $appointment->id)->first();
    expect($log->status)->toBe(NotificationStatus::Failed)
        ->and($log->error_message)->toContain('E.164')
        ->and($log->failed_at)->not->toBeNull();
});

test('channel sends the sms with the correct content and variables', function () {
    $tenant = vonageTenant(['name' => 'PawDesk Salone']);
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '+393331234567',
    ]);
    $pet = Pet::factory()->for($customer)->for($tenant)->create(['name' => 'Fido']);
    $service = Service::factory()->for($tenant)->create(['name' => 'Taglio pelo']);
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);
    $appointment->services()->attach($service, ['applied_price' => 3000, 'duration_minutes' => 60]);

    $sent = null;
    $this->mock(VonageSmsSender::class, function ($mock) use (&$sent) {
        $mock->shouldReceive('send')
            ->once()
            ->andReturnUsing(function (Tenant $t, string $to, string $content, ?string $from) use (&$sent) {
                $sent = ['to' => $to, 'content' => $content, 'from' => $from];

                return null;
            });
    });

    $channel = app(TenantVonageChannel::class);
    expect($channel->send($customer, new AppointmentConfirmedNotification($appointment)))->toBeTrue();

    $expected = sprintf(
        'PawDesk: appuntamento confermato per Fido il %s alle %s. (Taglio pelo) PawDesk Salone',
        $appointment->start_time->format('d/m/Y'),
        $appointment->start_time->format('H:i'),
    );

    expect($sent['to'])->toBe('+393331234567')
        ->and($sent['from'])->toBe('PawDesk')
        ->and($sent['content'])->toBe($expected);
});

test('channel propagates vonage errors so the queued job fails', function () {
    $tenant = vonageTenant();
    $customer = Customer::factory()->for($tenant)->create([
        'preferred_channel' => PreferredChannel::Sms,
        'phone' => '+393331234567',
    ]);
    $pet = Pet::factory()->for($customer)->for($tenant)->create();
    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'pet_id' => $pet->id,
    ]);

    $this->mock(VonageSmsSender::class, fn ($mock) => $mock->shouldReceive('send')
        ->andThrow(new App\Exceptions\VonageSmsException('Vonage SMS error: Throttled')));

    $channel = app(TenantVonageChannel::class);

    expect(fn () => $channel->send($customer, new AppointmentConfirmedNotification($appointment)))
        ->toThrow(App\Exceptions\VonageSmsException::class, 'Throttled');
});
