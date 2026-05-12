<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('password is auto-generated and hashed on user creation', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Password::shouldReceive('sendResetLink')->once();

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

    expect($createdUser)->not->toBeNull()
        ->and(Hash::check('password', $createdUser->password))->toBeFalse()
        ->and($createdUser->password)->not->toBe('password');
});

test('reset password action sends reset link email', function () {
    $targetUser = User::factory()->create();
    $targetUser->tenants()->attach($this->tenant);
    bootFilamentPanelAs($this->admin, $this->tenant);

    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => $targetUser->email]);

    Livewire::test(EditUser::class, ['record' => $targetUser->id])
        ->callAction('resetPassword')
        ->assertNotified();
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
