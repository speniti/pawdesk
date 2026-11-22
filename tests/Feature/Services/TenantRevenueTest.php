<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\Tenant;
use App\Models\Treatment;
use App\Services\TenantRevenue;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->service = app(TenantRevenue::class);
});

function revenueTreatment(Tenant $tenant, string $createdAt, int $finalPrice): Treatment
{
    $appointment = Appointment::factory()->forTenant($tenant)->create();

    return Treatment::factory()->forAppointment($appointment)
        ->create(['final_price' => $finalPrice, 'created_at' => Carbon::parse($createdAt)]);
}

test('aggregates monthly revenue for a year with zero-filled gaps', function () {
    revenueTreatment($this->tenant, '2026-01-10 10:00', 4500);
    revenueTreatment($this->tenant, '2026-01-20 10:00', 3000);
    revenueTreatment($this->tenant, '2026-03-05 10:00', 5000);

    $monthly = $this->service->monthlyForYear($this->tenant, 2026);

    expect($monthly)->toHaveCount(12)
        ->and($monthly[1])->toBe(7500)
        ->and($monthly[2])->toBe(0)
        ->and($monthly[3])->toBe(5000)
        ->and(array_sum($monthly))->toBe($this->service->totalForYear($this->tenant, 2026))
        ->and($this->service->totalForYear($this->tenant, 2026))->toBe(12500);
});

test('totals revenue between two dates', function () {
    revenueTreatment($this->tenant, '2026-02-01 00:00', 1000);
    revenueTreatment($this->tenant, '2026-04-30 23:59', 2000);
    revenueTreatment($this->tenant, '2026-06-15 12:00', 4000);

    $total = $this->service->totalBetween(
        $this->tenant,
        Carbon::parse('2026-02-01')->startOfDay(),
        Carbon::parse('2026-04-30')->endOfDay(),
    );

    expect($total)->toBe(3000);
});

test('ignores treatments of other tenants', function () {
    $otherTenant = Tenant::factory()->create();

    revenueTreatment($this->tenant, '2026-05-10 10:00', 1000);
    revenueTreatment($otherTenant, '2026-05-11 10:00', 9999);

    expect($this->service->totalForYear($this->tenant, 2026))->toBe(1000);
});
