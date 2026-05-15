<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

pest()->extend(TestCase::class)->use(LazilyRefreshDatabase::class)->in('Feature');

function bootFilamentPanel(Tenant $tenant, Panel|string|null $panel = null): void
{
    Filament::setCurrentPanel($panel ?? Filament::getDefaultPanel());
    Filament::setTenant($tenant);

    Filament::bootCurrentPanel();
}

function bootFilamentPanelAs(User $user, Tenant $tenant, ?string $panel = null): void
{
    actingAs($user);

    bootFilamentPanel($tenant, $panel);
}
