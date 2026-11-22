<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TenantRevenue
{
    /** @var array<int, array<int, array<int, int>>> */
    protected array $monthlyCache = [];

    /**
     * Revenue in cents for each month (keys 1-12) of the given year. Months
     * without treatments are zero, so charts always render 12 points.
     *
     * @return array<int, int>
     */
    public function monthlyForYear(Tenant $tenant, int $year): array
    {
        if (isset($this->monthlyCache[$tenant->id][$year])) {
            return $this->monthlyCache[$tenant->id][$year];
        }

        $treatments = $tenant->treatments()
            ->whereBetween('created_at', [Carbon::create($year)->startOfYear(), Carbon::create($year)->endOfYear()])
            ->get(['final_price', 'created_at']);

        $monthly = array_fill(1, 12, 0);

        foreach ($treatments as $treatment) {
            $monthly[$treatment->created_at->month] += $treatment->final_price;
        }

        return $this->monthlyCache[$tenant->id][$year] = $monthly;
    }

    /** Revenue in cents between the two bounds, inclusive. */
    public function totalBetween(Tenant $tenant, CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) $tenant->treatments()
            ->whereBetween('created_at', [$from, $to])
            ->sum('final_price');
    }

    public function totalForYear(Tenant $tenant, int $year): int
    {
        return array_sum($this->monthlyForYear($tenant, $year));
    }
}
