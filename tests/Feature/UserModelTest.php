<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('User has correct fillable attributes', function () {
    $user = new User;

    expect($user->getFillable())->toBe(['name', 'email', 'password']);
});

test('User hides sensitive attributes', function () {
    $user = new User;

    expect($user->getHidden())->toBe(['password', 'remember_token']);
});

test('User casts email_verified_at as datetime and password as hashed', function () {
    $user = new User;
    $casts = $user->getCasts();

    expect($casts)->toHaveKey('email_verified_at', 'datetime');
    expect($casts)->toHaveKey('password', 'hashed');
});

test('User factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user->name)->not->toBeEmpty();
    expect($user->email)->not->toBeEmpty();
    expect($user->email)->toContain('@');
    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
});
