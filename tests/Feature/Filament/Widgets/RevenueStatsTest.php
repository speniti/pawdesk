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
    // Pin the clock mid-year so `subDays(3)` treatments never fall into the
    // previous year between Jan 1 and Jan 3.
    Carbon::setTestNow('2026-06-15 10:00');

    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->admin->tenants()->attach($this->tenant);
});

afterEach(function () {
    Carbon::setTestNow();
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

it('shows the estimated taxes with fixed inps contributions', function () {
    $this->tenant->forceFill(['settings' => [
        'tax_coefficient' => 78,
        'tax_rate' => 15,
        'inps_mode' => 'fixed',
        'inps_fixed_amount' => 4500,
    ]])->save();

    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => 10_000, 'created_at' => Carbon::now()->subDays(3)]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    // €100 fatturato: imponibile 78, contributi fissi €4.500 sopra l'imponibile → imposta 0
    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText('Stima tasse anno')
        ->assertSeeText(Number::currency(4500.0, in: 'EUR', locale: 'it'));
});

it('flags the threshold in danger once 80% of the forfettario limit is reached', function () {
    expect(RevenueStats::thresholdColor(6_800_000))->toBe('danger')
        ->and(RevenueStats::thresholdColor(6_799_999))->toBe('gray');
});

it('compares against the same period of the previous year', function () {
    $appointment = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => 10_000, 'created_at' => Carbon::now()->subDays(3)]);

    $lastYearAppointment = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($lastYearAppointment)
        ->create(['final_price' => 5_000, 'created_at' => Carbon::parse('2025-03-10 10:00')]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(RevenueStats::class)
        ->assertOk()
        ->assertSeeText('+'.Number::percentage(100, maxPrecision: 1, locale: 'it').' vs stesso periodo 2025');
});

it('hides the stats widget from staff', function () {
    $staff = User::factory()->create();
    $staff->tenants()->attach($this->tenant);

    bootFilamentPanelAs($staff, $this->tenant);

    expect(RevenueStats::canView())->toBeFalse();
});
