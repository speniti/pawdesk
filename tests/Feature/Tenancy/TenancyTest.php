<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

test('User canAccessTenant checks tenant association', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user = User::factory()->forTenant($tenant1)->create();

    expect($user->canAccessTenant($tenant1))->toBeTrue()
        ->and($user->canAccessTenant($tenant2))->toBeFalse();
});
