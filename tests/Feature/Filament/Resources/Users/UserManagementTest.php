<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('creating a user from the manage modal sends them a magic link invite', function () {
    Notification::fake();

    bootFilamentPanelAs($this->admin, $this->tenant);

    $newUserData = User::factory()->make();

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'name' => $newUserData->name,
            'email' => $newUserData->email,
            'role' => 'staff',
        ])
        ->assertNotified();

    $createdUser = User::where('email', $newUserData->email)->first();

    expect($createdUser)->not->toBeNull()
        ->and($createdUser->tenants->pluck('id'))->toContain($this->tenant->id);

    Notification::assertSentTo($createdUser, MagicLinkNotification::class);
});

test('editing a user from the table modal updates the record', function () {
    $targetUser = User::factory()->create();
    $targetUser->tenants()->attach($this->tenant);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ManageUsers::class)
        ->callTableAction('edit', $targetUser, data: [
            'name' => 'Nome Aggiornato',
            'email' => $targetUser->email,
            'role' => 'admin',
        ])
        ->assertNotified();

    expect($targetUser->refresh())
        ->name->toBe('Nome Aggiornato')
        ->role->value->toBe('admin');
});

test('send access link table action sends a magic link email', function () {
    Notification::fake();

    $targetUser = User::factory()->create();
    $targetUser->tenants()->attach($this->tenant);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ManageUsers::class)
        ->callTableAction('sendAccessLink', $targetUser)
        ->assertNotified();

    Notification::assertSentTo($targetUser, MagicLinkNotification::class);
});

test('email must be unique', function () {
    User::factory()->create([
        'email' => 'duplicate@example.com',
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'name' => 'New User',
            'email' => 'duplicate@example.com',
            'role' => 'staff',
        ])
        ->assertHasFormErrors(['email' => 'unique']);
});

test('the authenticated user is not listed in the table', function () {
    $otherUser = User::factory()->create();
    $otherUser->tenants()->attach($this->tenant);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(ManageUsers::class)
        ->assertCanSeeTableRecords([$otherUser])
        ->assertCanNotSeeTableRecords([$this->admin]);
});
