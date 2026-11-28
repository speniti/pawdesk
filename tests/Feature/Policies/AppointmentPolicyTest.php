<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

test('a non-member gains no abilities on another tenant panel', function (User $outsider) {
    actingAs($outsider);
    bootFilamentPanel($this->tenant);

    // The outsider has the right role but no membership in the booted
    // tenant: the policy must deny every ability on its own (#92), not
    // lean on the panel middleware having already kept them out.
    expect($outsider->can('viewAny', Appointment::class))->toBeFalse()
        ->and($outsider->can('create', Appointment::class))->toBeFalse()
        ->and($outsider->can('view', $this->appointment))->toBeFalse()
        ->and($outsider->can('update', $this->appointment))->toBeFalse()
        ->and($outsider->can('delete', $this->appointment))->toBeFalse()
        ->and($outsider->can('restore', $this->appointment))->toBeFalse()
        ->and($outsider->can('forceDelete', $this->appointment))->toBeFalse();
})->with([
    'admin outsider' => fn () => User::factory()->admin()->forTenant(Tenant::factory()->create())->create(),
    'staff outsider' => fn () => User::factory()->forTenant(Tenant::factory()->create())->create(),
]);

test('membership is memoized across repeated policy checks', function () {
    actingAs($this->staff);
    bootFilamentPanel($this->tenant);

    // Prime the per-user memo: exactly one pivot query, then every
    // further check on the same user+tenant pair resolves in memory —
    // Filament authorizes table actions per row, so the membership
    // check must not multiply with the record count (#92).
    expect($this->staff->can('view', $this->appointment))->toBeTrue();

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($this->staff->can('update', $this->appointment))->toBeTrue()
        ->and($this->staff->can('delete', $this->appointment))->toBeTrue()
        ->and($this->staff->can('viewAny', Appointment::class))->toBeTrue();

    // Any query here is a failure by design: the pin is intentionally
    // strict so even an innocent lazy-load added to a policy path trips it.
    expect(DB::getQueryLog())->toBeEmpty();

    DB::disableQueryLog();
});

test('record abilities fail closed without a booted tenant', function () {
    actingAs($this->staff);

    // No panel is booted here, simulating ability checks made outside the
    // panel context (queue jobs, console commands): the policy must deny
    // every record-level ability even for a member of the record's tenant.
    expect($this->staff->can('view', $this->appointment))->toBeFalse()
        ->and($this->staff->can('update', $this->appointment))->toBeFalse()
        ->and($this->staff->can('delete', $this->appointment))->toBeFalse();
});

test('user without a tenant gains no abilities without a booted tenant', function () {
    $tenantless = User::factory()->create();

    actingAs($tenantless);

    // With membership verification (#92) the role-only abilities deny
    // without a booted tenant too: no tenant context means no membership
    // to prove, exactly like the panel-not-booted case.
    expect($tenantless->can('viewAny', Appointment::class))->toBeFalse()
        ->and($tenantless->can('create', Appointment::class))->toBeFalse();

    // Every record-level ability requires the record to belong to the
    // current tenant, and without a booted tenant there is none.
    expect($tenantless->can('view', $this->appointment))->toBeFalse()
        ->and($tenantless->can('update', $this->appointment))->toBeFalse()
        ->and($tenantless->can('delete', $this->appointment))->toBeFalse();
});
