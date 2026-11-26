<?php

declare(strict_types=1);

use App\Filament\Pages\RevenueDashboard\Widgets\RevenueChart;
use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-06-15 10:00');

    $this->tenant = Tenant::factory()->create();
    $this->admin = User::factory()->admin()->forTenant($this->tenant)->create();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('renders monthly revenue for the current and previous year in euros', function () {
    $january = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($january)
        ->create(['final_price' => 4500, 'created_at' => Carbon::parse('2026-01-10 10:00')]);

    $lastYearJuly = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($lastYearJuly)
        ->create(['final_price' => 2000, 'created_at' => Carbon::parse('2025-07-10 10:00')]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    Livewire::test(RevenueChart::class)
        ->assertOk()
        ->assertSeeText('Fatturato mensile');

    /** @var RevenueChart $chart */
    $chart = Livewire::test(RevenueChart::class)->instance();
    $getData = Closure::bind(fn (): array => $this->getData(), $chart, RevenueChart::class);
    $data = $getData();

    expect($data['labels'])->toBe(['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'])
        ->and($data['datasets'])->toHaveCount(2)
        ->and($data['datasets'][0]['label'])->toBe('2026')
        // Chart.js needs a 0-based list, or the 1-12 keyed array becomes a JSON object
        ->and(array_is_list($data['datasets'][0]['data']))->toBeTrue()
        ->and($data['datasets'][0]['data'])->toHaveCount(12)
        ->and($data['datasets'][0]['data'][0])->toBe(45.0)
        ->and($data['datasets'][1]['label'])->toBe('2025')
        ->and($data['datasets'][1]['data'][6])->toBe(20.0);
});

it('ignores treatments of other tenants', function () {
    $january = Appointment::factory()->forTenant($this->tenant)->create();
    Treatment::factory()->forAppointment($january)
        ->create(['final_price' => 4500, 'created_at' => Carbon::parse('2026-01-10 10:00')]);

    $otherTenant = Tenant::factory()->create();
    $otherAppointment = Appointment::factory()->forTenant($otherTenant)->create();
    Treatment::factory()->forAppointment($otherAppointment)
        ->create(['final_price' => 9999, 'created_at' => Carbon::parse('2026-01-11 10:00')]);

    bootFilamentPanelAs($this->admin, $this->tenant);

    /** @var RevenueChart $chart */
    $chart = Livewire::test(RevenueChart::class)->instance();
    $getData = Closure::bind(fn (): array => $this->getData(), $chart, RevenueChart::class);
    $data = $getData();

    expect($data['datasets'][0]['data'][0])->toBe(45.0)
        ->and(array_sum($data['datasets'][0]['data']))->toBe(45.0)
        ->and(array_sum($data['datasets'][1]['data']))->toBe(0.0);
});
