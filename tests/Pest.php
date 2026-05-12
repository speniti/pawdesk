<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

pest()->extend(TestCase::class)->use(LazilyRefreshDatabase::class)->in('Feature');

/**
 * Boot Filament admin panel for the given user and the current test's $tenant property.
 *
 * Requires $this->tenant to be set in the test's beforeEach() hook.
 */
function bootFilamentTenantAs(User $user): void
{
    actingAs($user);

    Filament::setCurrentPanel('admin');
    Filament::setTenant(test()->tenant);
    Filament::bootCurrentPanel();
}
