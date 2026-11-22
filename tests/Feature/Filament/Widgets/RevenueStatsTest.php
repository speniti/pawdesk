<?php

declare(strict_types=1);

use App\Filament\Pages\RevenueDashboard\Widgets\RevenueStats;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

it('shows year to date revenue and the 85k threshold usage', function () {
    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => 10_000, 'created_at' => Carbon::now()->subDays(3)]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText('Fatturato '.now()->year)
        ->assertSeeText(Number::currency(100.0, in: 'EUR', locale: 'it'))
        ->assertSeeText('Soglia forfettario');
});

it('shows the estimated taxes when fiscal settings are configured', function () {
    $this->tenant->forceFill(['settings' => [
        'tax_coefficient' => 78,
        'tax_rate' => 15,
        'inps_mode' => 'percentage',
        'inps_percentage' => 26,
    ]])->save();

    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => 10_000, 'created_at' => Carbon::now()->subDays(3)]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    // €100 fatturato: imponibile 78, INPS 20,28, imposta 8,66, totale 28,94
    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText('Stima tasse anno')
        ->assertSeeText(Number::currency(28.94, in: 'EUR', locale: 'it'))
        ->assertSeeText('Da accantonare');
});

it('does not count other tenants treatments', function () {
    $otherTenant = Tenant::factory()->create();
    $otherAppointment = Appointment::factory()->forTenant($otherTenant)->create();
    Treatment::factory()->forAppointment($otherAppointment)
        ->create(['final_price' => 99_999, 'created_at' => Carbon::now()->subDays(3)]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText(Number::currency(0.0, in: 'EUR', locale: 'it'));
});
