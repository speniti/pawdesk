<?php

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;

test('User implements HasTenants interface', function () {
    $user = new User;

    expect($user)->toBeInstanceOf(\Filament\Models\Contracts\HasTenants::class);
});

test('User implements FilamentUser interface', function () {
    $user = new User;

    expect($user)->toBeInstanceOf(\Filament\Models\Contracts\FilamentUser::class);
});

test('User getTenants returns tenants the user belongs to', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->tenants()->attach($tenant);

    $panel = Filament::getPanel('admin');
    $tenants = $user->getTenants($panel);

    expect($tenants->count())->toBe(1);
    expect($tenants->first()->id)->toBe($tenant->id);
});

test('User canAccessTenant returns true for associated tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->tenants()->attach($tenant);

    expect($user->canAccessTenant($tenant))->toBeTrue();
});

test('User canAccessTenant returns false for non-associated tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant1->id]);
    $user->tenants()->attach($tenant1);

    expect($user->canAccessTenant($tenant2))->toBeFalse();
});

test('Tenant has many users', function () {
    $tenant = Tenant::factory()->create();
    $user1 = User::factory()->create(['tenant_id' => $tenant->id]);
    $user2 = User::factory()->create(['tenant_id' => $tenant->id]);
    $tenant->users()->attach([$user1->id, $user2->id]);

    expect($tenant->users()->count())->toBe(2);
});

test('Filament admin panel has tenancy configured', function () {
    $panel = Filament::getPanel('admin');
    $tenantModel = $panel->getTenantModel();

    expect($tenantModel)->not->toBeNull();
    expect($tenantModel)->toBe(\App\Models\Tenant::class);
});

test('Filament admin panel tenant slug attribute is slug', function () {
    $panel = Filament::getPanel('admin');
    $slugAttribute = $panel->getTenantSlugAttribute();

    expect($slugAttribute)->toBe('slug');
});

test('ApplyTenantScopes middleware is registered on tenant routes', function () {
    $panel = Filament::getPanel('admin');
    $middleware = $panel->getTenantMiddleware();

    expect($middleware)->toContain(\App\Http\Middleware\ApplyTenantScopes::class);
});
