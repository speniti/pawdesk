<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);

    $this->staff = User::factory()->create();
    $this->staff->tenants()->attach($this->tenant);

    $targetUser = User::factory()->create();
    $targetUser->tenants()->attach($this->tenant);
    $this->targetUser = $targetUser;
});

test('user role determines access to user list', function (User $user, int $expectedStatus) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(ListUsers::class)->assertStatus($expectedStatus);
})->with([
    'admin can view' => [fn () => test()->admin, 200],
    'staff is forbidden' => [fn () => test()->staff, 403],
]);

test('user role determines access to create user page', function (User $user, int $expectedStatus) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(CreateUser::class)->assertStatus($expectedStatus);
})->with([
    'admin can view' => [fn () => test()->admin, 200],
    'staff is forbidden' => [fn () => test()->staff, 403],
]);

test('user role determines access to edit user page', function (User $user, int $expectedStatus) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(EditUser::class, ['record' => $this->targetUser->id])->assertStatus($expectedStatus);
})->with([
    'admin can view' => [fn () => test()->admin, 200],
    'staff is forbidden' => [fn () => test()->staff, 403],
]);
