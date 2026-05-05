<?php

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;

test('users from tenant A are not visible in tenant B context', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    Filament::setTenant($tenantA, isQuiet: true);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $users = User::all();

    expect($users)->toHaveCount(1);
    expect($users->first()->id)->toBe($userA->id);
    expect($users->pluck('id'))->not->toContain($userB->id);
});

test('switching tenant context changes visible data', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    User::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
    User::factory()->count(5)->create(['tenant_id' => $tenantB->id]);

    // Context: Tenant A
    Filament::setTenant($tenantA, isQuiet: true);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    expect(User::count())->toBe(3);

    // Context: Tenant B
    Filament::setTenant($tenantB, isQuiet: true);
    Filament::bootCurrentPanel();

    expect(User::count())->toBe(5);
});

test('new records are automatically scoped to current tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    Filament::setTenant($tenant, isQuiet: true);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'tenant_id' => null,
    ]);

    expect($user->tenant_id)->toBe($tenant->id);

    // Verify it's not visible in other tenant's context
    Filament::setTenant($otherTenant, isQuiet: true);
    Filament::bootCurrentPanel();

    expect(User::where('id', $user->id)->exists())->toBeFalse();
});
