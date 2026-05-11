<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;

test('creates tenant and admin with all options', function () {
    $this->artisan('app:setup', [
        '--tenant' => 'PawDesk',
        '--slug' => 'pawdesk',
        '--name' => 'Admin User',
        '--email' => 'admin@example.com',
        '--password' => 'secret123',
    ])->assertSuccessful();

    expect(Tenant::where('slug', 'pawdesk')->count())->toBe(1);
    $tenant = Tenant::where('slug', 'pawdesk')->first();
    expect($tenant->name)->toBe('PawDesk');

    expect(User::where('email', 'admin@example.com')->count())->toBe(1);
    $user = User::where('email', 'admin@example.com')->first();
    expect($user->name)->toBe('Admin User')
        ->and($user->role)->toBe(UserRole::Admin);

    expect($tenant->users()->where('users.id', $user->id)->exists())->toBeTrue();
});

test('is idempotent — running twice creates no duplicates', function () {
    $options = [
        '--tenant' => 'PawDesk',
        '--slug' => 'pawdesk',
        '--name' => 'Admin',
        '--email' => 'admin@example.com',
        '--password' => 'secret123',
    ];

    $this->artisan('app:setup', $options)->assertSuccessful();
    $this->artisan('app:setup', $options)->assertSuccessful();

    expect(Tenant::count())->toBe(1);
    expect(User::count())->toBe(1);

    $tenant = Tenant::first();
    expect($tenant->users()->count())->toBe(1);
});

test('generates slug from tenant name when slug is not provided', function () {
    $this->artisan('app:setup', [
        '--tenant' => 'My Clinic',
        '--email' => 'admin@clinic.com',
        '--password' => 'secret123',
    ])->assertSuccessful();

    $tenant = Tenant::first();
    expect($tenant->slug)->toBe('my-clinic');
});

test('fails without password in no-interaction mode', function () {
    $this->artisan('app:setup', [
        '--email' => 'admin@example.com',
        '--no-interaction' => true,
    ])->assertFailed();
});

test('associates existing user to new tenant without duplication', function () {
    $existingUser = User::factory()->create([
        'email' => 'admin@example.com',
        'role' => UserRole::Staff,
    ]);
    $firstTenant = Tenant::factory()->create();
    $existingUser->tenants()->attach($firstTenant);

    $this->artisan('app:setup', [
        '--tenant' => 'New Tenant',
        '--slug' => 'new-tenant',
        '--email' => 'admin@example.com',
        '--password' => 'secret123',
    ])->assertSuccessful();

    expect(User::count())->toBe(1);
    expect(Tenant::count())->toBe(2);

    $existingUser->refresh();
    expect($existingUser->tenants()->count())->toBe(2);
    expect($existingUser->tenants()->where('tenants.slug', 'new-tenant')->exists())->toBeTrue();
});
