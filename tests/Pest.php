<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

pest()->extend(TestCase::class)->use(LazilyRefreshDatabase::class)->in('Feature');

/**
 * Boot a Filament panel context for the given tenant.
 */
function bootFilamentPanel(Tenant $tenant, string $panel = 'admin'): void
{
    Filament::setCurrentPanel($panel);
    Filament::setTenant($tenant);
    Filament::bootCurrentPanel();
}

/**
 * Authenticate as the given user and boot the Filament admin panel for the given tenant.
 */
function bootFilamentPanelAs(User $user, Tenant $tenant, string $panel = 'admin'): void
{
    actingAs($user);
    bootFilamentPanel($tenant, $panel);
}
