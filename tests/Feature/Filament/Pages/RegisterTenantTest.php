<?php

declare(strict_types=1);

use App\Filament\Pages\Tenancy\RegisterTenant;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function bootPanelAsUserWithoutTenant(User $user): void
{
    actingAs($user);

    Filament::setCurrentPanel(Filament::getDefaultPanel());
    Filament::bootCurrentPanel();
}

describe('availability', function () {
    test('renders when no tenant exists', function () {
        $admin = User::factory()->admin()->create();

        bootPanelAsUserWithoutTenant($admin);

        Livewire::test(RegisterTenant::class)
            ->assertOk()
            ->assertSee('Registra il tuo salone');
    });

    test('returns 404 when a tenant already exists', function () {
        Tenant::factory()->create();

        $admin = User::factory()->admin()->create();

        bootPanelAsUserWithoutTenant($admin);

        Livewire::test(RegisterTenant::class)
            ->assertStatus(404);
    });
});

describe('registration', function () {
    test('creates the tenant with slug from name and attaches the registering user', function () {
        $admin = User::factory()->admin()->create();

        bootPanelAsUserWithoutTenant($admin);

        Livewire::test(RegisterTenant::class)
            ->fillForm(['name' => 'Salone da Simone'])
            ->call('register')
            ->assertHasNoFormErrors();

        $tenant = Tenant::query()->sole();

        expect($tenant->name)->toBe('Salone da Simone')
            ->and($tenant->slug)->toBe('salone-da-simone')
            ->and($admin->tenants()->whereKey($tenant)->exists())->toBeTrue();
    });

    test('name is required', function () {
        $admin = User::factory()->admin()->create();

        bootPanelAsUserWithoutTenant($admin);

        Livewire::test(RegisterTenant::class)
            ->fillForm(['name' => ''])
            ->call('register')
            ->assertHasFormErrors(['name']);

        expect(Tenant::query()->count())->toBe(0);
    });
});
