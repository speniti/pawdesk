<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('User factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user->name)->not->toBeEmpty();
    expect($user->email)->not->toBeEmpty();
    expect($user->email)->toContain('@');
    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->role)->toBe(UserRole::Staff);
});

test('User has role attribute', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    expect($user->role)->toBe(UserRole::Admin);
});

test('User has many appointments', function () {
    $user = User::factory()->create();
    $tenant = Tenant::find($user->tenant_id) ?? Tenant::factory()->create();

    Appointment::factory()->count(2)->create([
        'user_id' => $user->id,
        'tenant_id' => $tenant->id,
    ]);

    expect($user->appointments)->toHaveCount(2);
    expect($user->appointments->first())->toBeInstanceOf(Appointment::class);
});
