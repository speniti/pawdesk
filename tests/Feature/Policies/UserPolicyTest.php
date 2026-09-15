<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ManageUsers;
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

test('user role determines access to manage users page', function (User $user, int $expectedStatus) {
    actingAs($user);
    bootFilamentPanel($this->tenant);

    Livewire::test(ManageUsers::class)->assertStatus($expectedStatus);
})->with([
    'admin can view' => [fn () => test()->admin, 200],
    'staff is forbidden' => [fn () => test()->staff, 403],
]);

test('admin sees the modal actions on the manage users page', function () {
    actingAs($this->admin);
    bootFilamentPanel($this->tenant);

    Livewire::test(ManageUsers::class)
        ->assertActionVisible('create')
        ->assertTableActionVisible('edit', $this->targetUser)
        ->assertTableActionVisible('sendAccessLink', $this->targetUser)
        ->assertTableActionVisible('delete', $this->targetUser);
});
