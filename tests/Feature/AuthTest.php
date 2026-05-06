<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

test('login with correct credentials redirects to tenant dashboard', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->tenants()->attach($tenant);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect("/admin/{$tenant->slug}");
});

test('login with wrong credentials shows validation error', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);
});

test('logout destroys session', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->tenants()->attach($tenant);

    $this->actingAs($user);

    $this->post('/admin/logout')->assertRedirect('/admin/login');

    $this->assertGuest();
});
