<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();

    $this->staff = User::factory()->forTenant($this->tenant)->create();

    $targetUser = User::factory()->forTenant($this->tenant)->create();
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

test('only admins can bulk delete users', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    expect($this->admin->can('deleteAny', User::class))->toBeTrue()
        ->and($this->staff->can('deleteAny', User::class))->toBeFalse();
});

test('admin cannot delete themselves', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    expect($this->admin->can('delete', $this->admin))->toBeFalse();
});

test('admin cannot delete a user who does not belong to the current tenant', function () {
    $outsider = User::factory()->forTenant(Tenant::factory()->create())->create();

    bootFilamentPanelAs($this->admin, $this->tenant);

    expect($this->admin->can('delete', $outsider))->toBeFalse();
});

test('a non-member admin gains no abilities on another tenant panel', function () {
    $outsider = User::factory()->admin()->forTenant(Tenant::factory()->create())->create();

    actingAs($outsider);
    bootFilamentPanel($this->tenant);

    // The outsider has the admin role but no membership in the booted
    // tenant: the policy must deny every ability on its own (#92), not
    // lean on the panel middleware having already kept them out.
    expect($outsider->can('viewAny', User::class))->toBeFalse()
        ->and($outsider->can('create', User::class))->toBeFalse()
        ->and($outsider->can('deleteAny', User::class))->toBeFalse()
        ->and($outsider->can('view', $this->targetUser))->toBeFalse()
        ->and($outsider->can('update', $this->targetUser))->toBeFalse()
        ->and($outsider->can('delete', $this->targetUser))->toBeFalse()
        ->and($outsider->can('restore', $this->targetUser))->toBeFalse()
        ->and($outsider->can('forceDelete', $this->targetUser))->toBeFalse();
});
