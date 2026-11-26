<?php

declare(strict_types=1);

use App\Filament\Pages\RevenueDashboard;
use App\Filament\Pages\RevenueDashboard\Widgets\RevenueChart;
use App\Filament\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();
});

describe('authorization', function () {
    test('admin can access the revenue dashboard', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(RevenueDashboard::class)->assertOk();
    });

    test('staff cannot access the revenue dashboard', function () {
        $staff = User::factory()->forTenant($this->tenant)->create();

        bootFilamentPanelAs($staff, $this->tenant);

        Livewire::test(RevenueDashboard::class)->assertStatus(403);
    });

    test('admin from another tenant cannot access the revenue dashboard', function () {
        $otherTenant = Tenant::factory()->create();
        $otherAdmin = User::factory()->admin()->forTenant($otherTenant)->create();

        bootFilamentPanelAs($otherAdmin, $this->tenant);

        Livewire::test(RevenueDashboard::class)->assertStatus(404);
    });
});

describe('content', function () {
    test('shows the unconfigured notice when fiscal settings are missing', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(RevenueDashboard::class)
            ->assertOk()
            ->assertSeeText('Stima tasse non disponibile');
    });

    test('shows the estimate breakdown when fiscal settings are configured', function () {
        $this->tenant->forceFill(['settings' => [
            'tax_coefficient' => 78,
            'tax_rate' => 15,
            'inps_mode' => 'percentage',
            'inps_percentage' => 26,
        ]])->save();

        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(RevenueDashboard::class)
            ->assertOk()
            ->assertSeeText('Imponibile')
            ->assertSeeText('Imposta sostitutiva')
            ->assertSeeText('Accantonamento consigliato');
    });

    test('shows the fixed inps regime label when configured with fixed contributions', function () {
        $this->tenant->forceFill(['settings' => [
            'tax_coefficient' => 78,
            'tax_rate' => 15,
            'inps_mode' => 'fixed',
            'inps_fixed_amount' => 4500,
        ]])->save();

        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(RevenueDashboard::class)
            ->assertOk()
            ->assertSeeText('Contributi fissi (artigiani/commercianti)');
    });
});

test('the revenue chart widget spans the full page width', function () {
    // Filament lays out header widgets on a 2-column grid by default: the
    // stats widget spans it fully, but the chart would inherit the base
    // Widget columnSpan of 1 and sit in a half-width cell (#98).
    expect((new RevenueChart)->getColumnSpan())->toBe('full');
});

test('the revenue page registers inside the Gestione navigation group', function () {
    // Admin bookkeeping surfaces live in the Gestione group like the Users
    // resource; ungrouped, the page floated at the sidebar root and its
    // sort 10 collided with Customers (#99). It comes after Utenti (10),
    // and the label is pinned against UserResource to prevent drift.
    expect(RevenueDashboard::getNavigationGroup())
        ->toBe('Gestione')
        ->toBe(UserResource::getNavigationGroup())
        ->and(RevenueDashboard::getNavigationSort())->toBe(20);
});
