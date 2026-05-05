<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

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
