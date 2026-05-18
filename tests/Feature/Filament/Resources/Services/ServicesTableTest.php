<?php

declare(strict_types=1);

use App\Enums\ServiceStatus;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

it('shows only active services by default', function () {
    Service::factory()->count(3)->create([
        'status' => ServiceStatus::Active,
        'tenant_id' => $this->tenant->id,
    ]);
    Service::factory()->count(2)->create([
        'status' => ServiceStatus::Archived,
        'tenant_id' => $this->tenant->id,
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ListServices::class)
        ->assertCanSeeTableRecords(Service::where('status', ServiceStatus::Active)->get())
        ->assertCountTableRecords(3);
});
