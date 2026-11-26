<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->staff = User::factory()->forTenant($this->tenant)->create();

    $this->appointment = Appointment::factory()->forTenant($this->tenant)->create();
});

test('user with access can perform every appointment ability in their tenant', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    expect($user->can('viewAny', Appointment::class))->toBeTrue()
        ->and($user->can('view', $this->appointment))->toBeTrue()
        ->and($user->can('create', Appointment::class))->toBeTrue()
        ->and($user->can('update', $this->appointment))->toBeTrue()
        ->and($user->can('delete', $this->appointment))->toBeTrue()
        ->and($user->can('restore', $this->appointment))->toBeTrue()
        ->and($user->can('forceDelete', $this->appointment))->toBeTrue();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('users cannot act on appointments of another tenant', function (User $user) {
    $otherTenant = Tenant::factory()->create();
    $foreignAppointment = Appointment::factory()->forTenant($otherTenant)->create();

    actingAs($user);
    bootFilamentPanel($this->tenant);

    // The foreign record genuinely exists outside the current tenancy scope.
    expect(Appointment::withoutGlobalScopes()->findOrFail($foreignAppointment->id))
        ->tenant_id->toBe($otherTenant->id);

    expect($user->can('view', $foreignAppointment))->toBeFalse()
        ->and($user->can('update', $foreignAppointment))->toBeFalse()
        ->and($user->can('delete', $foreignAppointment))->toBeFalse()
        ->and($user->can('restore', $foreignAppointment))->toBeFalse()
        ->and($user->can('forceDelete', $foreignAppointment))->toBeFalse();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('record abilities fail closed without a booted tenant', function () {
    actingAs($this->staff);

    // No panel is booted here, simulating ability checks made outside the
    // panel context (queue jobs, console commands): the policy must deny
    // every record-level ability even for a member of the record's tenant.
    expect($this->staff->can('view', $this->appointment))->toBeFalse()
        ->and($this->staff->can('update', $this->appointment))->toBeFalse()
        ->and($this->staff->can('delete', $this->appointment))->toBeFalse();
});

test('user without a tenant keeps role-only abilities but gains no record access', function () {
    $tenantless = User::factory()->create();

    actingAs($tenantless);

    // viewAny and create intentionally check the role only: a tenant-less
    // user cannot reach any tenant panel, so there is no list to render nor
    // a tenant to create into. This pins the current boundary of the policy.
    expect($tenantless->can('viewAny', Appointment::class))->toBeTrue()
        ->and($tenantless->can('create', Appointment::class))->toBeTrue();

    // Every record-level ability requires the record to belong to the
    // current tenant, and without a booted tenant there is none.
    expect($tenantless->can('view', $this->appointment))->toBeFalse()
        ->and($tenantless->can('update', $this->appointment))->toBeFalse()
        ->and($tenantless->can('delete', $this->appointment))->toBeFalse();
});
