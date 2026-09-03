<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
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

test('creating a user sends them a magic link invite', function () {
    Notification::fake();

    bootFilamentPanelAs($this->admin, $this->tenant);

    $newUserData = User::factory()->make();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => $newUserData->name,
            'email' => $newUserData->email,
            'role' => 'staff',
        ])
        ->call('create')
        ->assertNotified();

    $createdUser = User::where('email', $newUserData->email)->first();

    expect($createdUser)->not->toBeNull();

    Notification::assertSentTo($createdUser, MagicLinkNotification::class);
});

test('send access link action sends a magic link email', function () {
    Notification::fake();

    $targetUser = User::factory()->create();
    $targetUser->tenants()->attach($this->tenant);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(EditUser::class, ['record' => $targetUser->id])
        ->callAction('sendAccessLink')
        ->assertNotified();

    Notification::assertSentTo($targetUser, MagicLinkNotification::class);
});

test('email must be unique', function () {
    User::factory()->create([
        'email' => 'duplicate@example.com',
    ]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New User',
            'email' => 'duplicate@example.com',
            'role' => 'staff',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});
