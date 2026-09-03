<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Profile;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

test('profile renders without password fields', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(Profile::class)
        ->assertOk()
        ->assertFormFieldDoesNotExist('password')
        ->assertFormFieldDoesNotExist('passwordConfirmation')
        ->assertFormFieldDoesNotExist('currentPassword');
});

test('profile name can be updated', function () {
    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(Profile::class)
        ->fillForm(['name' => 'Nome Aggiornato'])
        ->call('save')
        ->assertNotified();

    expect($this->admin->refresh()->name)->toBe('Nome Aggiornato');
});
