<?php

declare(strict_types=1);

use App\Enums\ServiceStatus;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Resources\Services\Pages\ViewService;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->otherTenant = Tenant::factory()->create();

    // Both services are explicitly active so the table's default status
    // filter cannot mask the tenant scoping being asserted below.
    $this->foreignService = Service::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'name' => 'ServizioDiUnAltroTenant',
        'base_price' => 999999,
        'status' => ServiceStatus::Active->value,
    ]);

    $this->service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'ServizioNostro',
        'status' => ServiceStatus::Active->value,
    ]);
});

test('service list is scoped to the current tenant', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    // The foreign service genuinely exists outside the tenancy scope.
    expect(Service::withoutGlobalScopes()->findOrFail($this->foreignService->id))
        ->tenant_id->toBe($this->otherTenant->id);

    Livewire::test(ListServices::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$this->service])
        ->assertCanNotSeeTableRecords([$this->foreignService])
        ->assertDontSee('ServizioDiUnAltroTenant');
});

test('service view page returns 404 for a foreign tenant service', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewService::class, ['record' => $this->foreignService->id])
        ->assertStatus(404);
});

test('service edit page returns 404 for a foreign tenant service', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditService::class, ['record' => $this->foreignService->id])
        ->assertStatus(404);

    // The foreign record (which carries prices) exists and is untouched.
    expect(Service::withoutGlobalScopes()->findOrFail($this->foreignService->id))
        ->base_price->toBe(999999);
});
