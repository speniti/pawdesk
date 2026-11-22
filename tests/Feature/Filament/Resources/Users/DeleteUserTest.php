<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create();
    $this->tenantB = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenantA);
});

test('deleting a user who also belongs to another salon detaches them from the current salon only', function () {
    $sharedUser = User::factory()->create();
    $sharedUser->tenants()->attach([$this->tenantA->id, $this->tenantB->id]);

    bootFilamentPanelAs($this->admin, $this->tenantA);

    Livewire::test(ManageUsers::class)
        ->callTableAction('delete', $sharedUser)
        ->assertNotified();

    expect(User::whereKey($sharedUser)->exists())->toBeTrue();

    assertDatabaseMissing('tenant_user', [
        'tenant_id' => $this->tenantA->id,
        'user_id' => $sharedUser->id,
    ]);

    assertDatabaseHas('tenant_user', [
        'tenant_id' => $this->tenantB->id,
        'user_id' => $sharedUser->id,
    ]);
});

test('deleting a user who only belongs to the current salon deletes the record', function () {
    $monoTenantUser = User::factory()->create();
    $monoTenantUser->tenants()->attach($this->tenantA);

    bootFilamentPanelAs($this->admin, $this->tenantA);

    Livewire::test(ManageUsers::class)
        ->callTableAction('delete', $monoTenantUser)
        ->assertNotified();

    expect(User::whereKey($monoTenantUser)->exists())->toBeFalse();

    assertDatabaseMissing('tenant_user', [
        'tenant_id' => $this->tenantA->id,
        'user_id' => $monoTenantUser->id,
    ]);
});

test('bulk deleting mixed users detaches the shared user and deletes the mono-tenant one', function () {
    $sharedUser = User::factory()->create();
    $sharedUser->tenants()->attach([$this->tenantA->id, $this->tenantB->id]);

    $monoTenantUser = User::factory()->create();
    $monoTenantUser->tenants()->attach($this->tenantA);

    bootFilamentPanelAs($this->admin, $this->tenantA);

    Livewire::test(ManageUsers::class)
        ->callTableBulkAction('delete', [$sharedUser, $monoTenantUser])
        ->assertNotified();

    expect(User::whereKey($sharedUser)->exists())->toBeTrue()
        ->and(User::whereKey($monoTenantUser)->exists())->toBeFalse();

    assertDatabaseMissing('tenant_user', [
        'tenant_id' => $this->tenantA->id,
        'user_id' => $sharedUser->id,
    ]);

    assertDatabaseHas('tenant_user', [
        'tenant_id' => $this->tenantB->id,
        'user_id' => $sharedUser->id,
    ]);
});

test('the delete confirmation warns that a shared user will only be removed from the current salon', function () {
    $sharedUser = User::factory()->create();
    $sharedUser->tenants()->attach([$this->tenantA->id, $this->tenantB->id]);

    bootFilamentPanelAs($this->admin, $this->tenantA);

    $page = Livewire::test(ManageUsers::class);

    $page->mountTableAction('delete', $sharedUser);

    $warning = $page->instance()->getMountedAction()?->getModalDescription();

    expect($warning)->toContain('appartiene anche ad altri saloni')
        ->and($warning)->toContain("rimossa solo l'appartenenza a questo salone");
});

test('the delete confirmation does not warn about other salons for a mono-tenant user', function () {
    $monoTenantUser = User::factory()->create();
    $monoTenantUser->tenants()->attach($this->tenantA);

    bootFilamentPanelAs($this->admin, $this->tenantA);

    $page = Livewire::test(ManageUsers::class);

    $page->mountTableAction('delete', $monoTenantUser);

    $description = (string) $page->instance()->getMountedAction()?->getModalDescription();

    expect($description)->not->toContain('altri saloni');
});
