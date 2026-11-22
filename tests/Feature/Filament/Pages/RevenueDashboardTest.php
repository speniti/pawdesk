<?php

declare(strict_types=1);

use App\Filament\Pages\RevenueDashboard;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

describe('authorization', function () {
    test('admin can access the revenue dashboard', function () {
        bootFilamentPanelAs($this->admin, $this->tenant);

        Livewire::test(RevenueDashboard::class)->assertOk();
    });

    test('staff cannot access the revenue dashboard', function () {
        $staff = User::factory()->create();
        $staff->tenants()->attach($this->tenant);

        bootFilamentPanelAs($staff, $this->tenant);

        Livewire::test(RevenueDashboard::class)->assertStatus(403);
    });

    test('admin from another tenant cannot access the revenue dashboard', function () {
        $otherTenant = Tenant::factory()->create();
        $otherAdmin = User::factory()->admin()->create();
        $otherAdmin->tenants()->attach($otherTenant);

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
