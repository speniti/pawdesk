<?php

declare(strict_types=1);

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->staff = User::factory()->forTenant($this->tenant)->create();

    $this->service = Service::factory()->create(['tenant_id' => $this->tenant->id]);
});

test('user with access can view service list', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(ListServices::class)->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('user with access can view create service page', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(CreateService::class)->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('user with access can view edit service page', function (User $user) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(EditService::class, ['record' => $this->service->id])->assertOk();
})->with([
    'admin' => fn () => test()->admin,
    'staff' => fn () => test()->staff,
]);

test('staff cannot update or delete services of another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $foreignService = Service::factory()->create(['tenant_id' => $otherTenant->id]);

    actingAs($this->staff);
    bootFilamentPanel($this->tenant);

    // The foreign record genuinely exists outside the current tenancy scope.
    expect(Service::withoutGlobalScopes()->findOrFail($foreignService->id))
        ->tenant_id->toBe($otherTenant->id);

    expect($this->staff->can('view', $foreignService))->toBeFalse()
        ->and($this->staff->can('update', $foreignService))->toBeFalse()
        ->and($this->staff->can('delete', $foreignService))->toBeFalse();
});

test('a non-member gains no abilities on another tenant panel', function (User $outsider) {
    actingAs($outsider);
    bootFilamentPanel($this->tenant);

    // The outsider has the right role but no membership in the booted
    // tenant: the policy must deny every ability on its own (#92), not
    // lean on the panel middleware having already kept them out.
    expect($outsider->can('viewAny', Service::class))->toBeFalse()
        ->and($outsider->can('create', Service::class))->toBeFalse()
        ->and($outsider->can('view', $this->service))->toBeFalse()
        ->and($outsider->can('update', $this->service))->toBeFalse()
        ->and($outsider->can('delete', $this->service))->toBeFalse()
        ->and($outsider->can('restore', $this->service))->toBeFalse()
        ->and($outsider->can('forceDelete', $this->service))->toBeFalse();
})->with([
    'admin outsider' => fn () => User::factory()->admin()->forTenant(Tenant::factory()->create())->create(),
    'staff outsider' => fn () => User::factory()->forTenant(Tenant::factory()->create())->create(),
]);
