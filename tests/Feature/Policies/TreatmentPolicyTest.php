<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->staff = User::factory()->create();
    $this->staff->tenants()->attach($this->tenant);

    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    $this->treatment = Treatment::factory()->forAppointment($appointment)->create();
});

test('user with access can view and update treatments in their tenant', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    expect($user->can('viewAny', Treatment::class))->toBeTrue()
        ->and($user->can('view', $this->treatment))->toBeTrue()
        ->and($user->can('update', $this->treatment))->toBeTrue();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('treatment creation and deletion are denied to everyone', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    expect($user->can('create', Treatment::class))->toBeFalse()
        ->and($user->can('delete', $this->treatment))->toBeFalse()
        ->and($user->can('forceDelete', $this->treatment))->toBeFalse()
        ->and($user->can('restore', $this->treatment))->toBeFalse();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('staff cannot update treatments of another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $otherAppointment = Appointment::factory()->forTenant($otherTenant)->create();
    $otherTreatment = Treatment::factory()->forAppointment($otherAppointment)->create();

    actingAs($this->staff);
    bootFilamentPanel($this->tenant);

    expect($this->staff->can('view', $otherTreatment))->toBeFalse()
        ->and($this->staff->can('update', $otherTreatment))->toBeFalse();
});
