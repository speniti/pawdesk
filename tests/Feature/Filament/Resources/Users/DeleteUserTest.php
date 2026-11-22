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
        ->assertNotified('Rimosso da questo salone');

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
        ->assertNotified(trans('filament-actions::delete.single.notifications.deleted.title'));

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
        ->assertNotified(trans('filament-actions::delete.multiple.notifications.deleted.title'));

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

test('bulk deleting skips users the admin cannot delete in the current tenant', function () {
    $sharedUser = User::factory()->create();
    $sharedUser->tenants()->attach([$this->tenantA->id, $this->tenantB->id]);

    // Not attached to any tenant: the delete policy denies it, so a crafted
    // bulk selection must not process (and hard-delete) the record.
    $outsider = User::factory()->create();

    bootFilamentPanelAs($this->admin, $this->tenantA);

    // Wire-level guarantee: the per-record delete policy is enforced on top
    // of the tenant-scoped table query that already hides foreign users.
    $page = Livewire::test(ManageUsers::class);
    $page->mountTableBulkAction('delete', [$sharedUser, $outsider]);

    expect($page->instance()->getMountedAction()?->shouldAuthorizeIndividualRecords())->toBeTrue();

    Livewire::test(ManageUsers::class)
        ->callTableBulkAction('delete', [$sharedUser, $outsider]);

    expect(User::whereKey($outsider)->exists())->toBeTrue()
        ->and($outsider->tenants()->exists())->toBeFalse();

    assertDatabaseMissing('tenant_user', [
        'tenant_id' => $this->tenantA->id,
        'user_id' => $sharedUser->id,
    ]);
});

test('the bulk delete confirmation states how many selected users belong to other salons', function () {
    $sharedUser = User::factory()->create();
    $sharedUser->tenants()->attach([$this->tenantA->id, $this->tenantB->id]);

    $monoTenantUser = User::factory()->create();
    $monoTenantUser->tenants()->attach($this->tenantA);

    bootFilamentPanelAs($this->admin, $this->tenantA);

    $page = Livewire::test(ManageUsers::class);

    $page->mountTableBulkAction('delete', [$sharedUser, $monoTenantUser]);

    $warning = (string) $page->instance()->getMountedAction()?->getModalDescription();

    expect($warning)->toContain('1 utente selezionato appartiene anche ad altri saloni')
        ->and($warning)->toContain('rimosso solo da questo salone');
});

test('the bulk delete confirmation does not mention other salons when no selected user is shared', function () {
    $monoTenantUser = User::factory()->create();
    $monoTenantUser->tenants()->attach($this->tenantA);

    bootFilamentPanelAs($this->admin, $this->tenantA);

    $page = Livewire::test(ManageUsers::class);

    $page->mountTableBulkAction('delete', [$monoTenantUser]);

    $description = (string) $page->instance()->getMountedAction()?->getModalDescription();

    expect($description)->not->toContain('altri saloni');
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
