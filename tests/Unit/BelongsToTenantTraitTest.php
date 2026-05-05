<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

test('BelongsToTenant trait applies tenant_id global scope', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
    $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

    Filament::setTenant($tenant1, isQuiet: true);

    $users = User::all();

    expect($users)->toHaveCount(1);
    expect($users->first()->id)->toBe($user1->id);
});

test('BelongsToTenant trait automatically sets tenant_id on create', function () {
    $tenant = Tenant::factory()->create();

    Filament::setTenant($tenant, isQuiet: true);

    $user = User::factory()->make(['tenant_id' => null]);
    $user->save();

    expect($user->tenant_id)->toBe($tenant->id);
});

test('BelongsToTenant trait does not override explicit tenant_id', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    Filament::setTenant($tenant1, isQuiet: true);

    $user = User::factory()->create(['tenant_id' => $tenant2->id]);

    expect($user->tenant_id)->toBe($tenant2->id);
});
