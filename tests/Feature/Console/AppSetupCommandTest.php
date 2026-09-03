<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;

test('creates tenant and admin with all options and sends a magic link', function () {
    Notification::fake();

    $this->artisan('app:setup', [
        '--tenant' => 'PawDesk',
        '--slug' => 'pawdesk',
        '--name' => 'Admin User',
        '--email' => 'admin@example.com',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('/auth/magic-link/');

    expect(Tenant::where('slug', 'pawdesk')->count())->toBe(1);
    $tenant = Tenant::where('slug', 'pawdesk')->first();
    expect($tenant->name)->toBe('PawDesk');

    expect(User::where('email', 'admin@example.com')->count())->toBe(1);
    $user = User::where('email', 'admin@example.com')->first();
    expect($user->name)->toBe('Admin User')
        ->and($user->role)->toBe(UserRole::Admin);

    expect($tenant->users()->where('users.id', $user->id)->exists())->toBeTrue();

    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('is idempotent — running twice creates no duplicates and sends one link', function () {
    Notification::fake();

    $options = [
        '--tenant' => 'PawDesk',
        '--slug' => 'pawdesk',
        '--name' => 'Admin',
        '--email' => 'admin@example.com',
    ];

    $this->artisan('app:setup', $options)->assertSuccessful();
    $this->artisan('app:setup', $options)->assertSuccessful();

    expect(Tenant::count())->toBe(1);
    expect(User::count())->toBe(1);

    $tenant = Tenant::first();
    expect($tenant->users()->count())->toBe(1);

    Notification::assertSentTo(User::first(), MagicLinkNotification::class, 1);
});

test('generates slug from tenant name when slug is not provided', function () {
    Notification::fake();

    $this->artisan('app:setup', [
        '--tenant' => 'My Clinic',
        '--email' => 'admin@clinic.com',
    ])->assertSuccessful();

    $tenant = Tenant::first();
    expect($tenant->slug)->toBe('my-clinic');
});

test('fails without email', function () {
    $this->artisan('app:setup')->assertFailed();
});

test('associates existing user to new tenant without duplication', function () {
    Notification::fake();

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
    ])->assertSuccessful();

    expect(User::count())->toBe(1);
    expect(Tenant::count())->toBe(2);

    $existingUser->refresh();
    expect($existingUser->tenants()->count())->toBe(2);
    expect($existingUser->tenants()->where('tenants.slug', 'new-tenant')->exists())->toBeTrue();

    Notification::assertNothingSent();
});
