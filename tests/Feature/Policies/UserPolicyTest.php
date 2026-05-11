<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->staff = User::factory()->create();
    $this->staff->tenants()->attach($this->tenant);
});

test('admin can view user list', function () {
    bootFilamentTenantAs($this->admin);

    Livewire::test(ListUsers::class)->assertOk();
});

test('staff cannot view user list', function () {
    bootFilamentTenantAs($this->staff);

    Livewire::test(ListUsers::class)->assertForbidden();
});

test('admin can view create user page', function () {
    bootFilamentTenantAs($this->admin);

    Livewire::test(CreateUser::class)->assertOk();
});

test('staff cannot view create user page', function () {
    bootFilamentTenantAs($this->staff);

    Livewire::test(CreateUser::class)->assertForbidden();
});

test('admin can view edit user page', function () {
    $targetUser = User::factory()->create();
    $targetUser->tenants()->attach($this->tenant);
    bootFilamentTenantAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $targetUser->id])->assertOk();
});

test('staff cannot view edit user page', function () {
    $targetUser = User::factory()->create();
    $targetUser->tenants()->attach($this->tenant);
    bootFilamentTenantAs($this->staff);

    Livewire::test(EditUser::class, ['record' => $targetUser->id])->assertForbidden();
});
