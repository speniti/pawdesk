<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

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
        ->assertRedirect("/$tenant->slug");
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
    actingAs($user = User::factory()->create());
    post('/logout')->assertRedirect('/login');

    assertGuest();
});
