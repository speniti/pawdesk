<?php

declare(strict_types=1);

use App\Enums\ServiceStatus;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ViewService;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('can read a service', function () {
    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Full Groom',
        'description' => 'Complete grooming service',
        'category' => 'grooming',
        'duration_minutes' => 60,
        'base_price' => 5000,
        'status' => ServiceStatus::Active,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ViewService::class, ['record' => $service->id])
        ->assertOk()
        ->assertSee('Full Groom')
        ->assertSee('Complete grooming service')
        ->assertSee('60 min')
        ->assertSee('50,00 €');

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => 'Full Groom',
        'tenant_id' => $this->tenant->id,
    ]);
});

test('can update a service', function () {
    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Bath & Brush',
        'duration_minutes' => 30,
        'base_price' => 2500,
        'status' => ServiceStatus::Active,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditService::class, ['record' => $service->id])
        ->fillForm([
            'name' => 'Bath & Brush Premium',
            'description' => 'Premium bath and brush service',
            'category' => 'bath',
            'duration_minutes' => 45,
            'base_price' => 35.50,
            'status' => 'active',
            'combinable' => true,
        ])
        ->call('save')
        ->assertNotified();

    expect($service->refresh())
        ->name->toBe('Bath & Brush Premium')
        ->description->toBe('Premium bath and brush service')
        ->duration_minutes->toBe(45)
        ->base_price->toBe(3550)
        ->status->toBe(ServiceStatus::Active);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'name' => 'Bath & Brush Premium',
        'duration_minutes' => 45,
        'base_price' => 3550,
    ]);
});

test('can archive a service instead of deleting it', function () {
    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Old Service',
        'status' => ServiceStatus::Active,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditService::class, ['record' => $service->id])
        ->fillForm([
            'name' => $service->name,
            'description' => $service->description,
            'category' => $service->category->value,
            'duration_minutes' => $service->duration_minutes,
            'base_price' => $service->base_price / 100,
            'status' => 'archived',
            'combinable' => $service->combinable,
        ])
        ->call('save')
        ->assertNotified();

    expect($service->refresh()->status)->toBe(ServiceStatus::Archived);

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'status' => 'archived',
    ]);

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
        'status' => 'active',
    ]);

    expect(Service::find($service->id))->not->toBeNull()
        ->and($service->refresh()->status->value)->toBe('archived');
});

test('can delete a service', function () {
    $service = Service::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Service to Delete',
        'status' => ServiceStatus::Active,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditService::class, ['record' => $service->id])
        ->callAction('delete')
        ->assertNotified();

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
    ]);

    expect(Service::find($service->id))->toBeNull();
});
