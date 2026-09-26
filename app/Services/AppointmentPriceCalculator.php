<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pet;
use App\Models\Service;
use Illuminate\Support\Collection;

class AppointmentPriceCalculator
{
    /**
     * @param  Collection<int, Service>  $services
     * @return array<int, array{applied_price: int, duration_minutes: int}>
     */
    public static function buildPivotData(Collection $services, ?Pet $pet): array
    {
        return $services->mapWithKeys(fn (Service $service) => [
            $service->id => [
                'applied_price' => self::resolvePrice($service, $pet),
                'duration_minutes' => self::resolveDuration($service, $pet),
            ],
        ])->all();
    }

    public static function resolveDuration(Service $service, ?Pet $pet): int
    {
        /** @var array{duration_minutes?: int}|null $variation */
        $variation = self::matchingVariation($service, $pet);

        return $variation !== null ? (int) $variation['duration_minutes'] : $service->duration_minutes;
    }

    public static function resolvePrice(Service $service, ?Pet $pet): int
    {
        /** @var array{price?: int}|null $variation */
        $variation = self::matchingVariation($service, $pet);

        return $variation !== null ? (int) $variation['price'] : $service->base_price;
    }

    /**
     * @param  Collection<int, Service>  $services
     */
    public static function totalDuration(Collection $services, ?Pet $pet): int
    {
        return $services->sum(fn (Service $service): int => self::resolveDuration($service, $pet));
    }

    /**
     * A variation applies only on an exact size + coat match: pets without a
     * coat (or without any variation matching their combination) always get
     * the service's base price and duration.
     *
     * @return array{size: string, coat: string, price: int, duration_minutes: int}|null
     */
    private static function matchingVariation(Service $service, ?Pet $pet): ?array
    {
        $petSize = $pet?->size?->value;
        $petCoat = $pet?->coat?->value;

        if ($petSize === null || $petCoat === null) {
            return null;
        }

        /** @var array<array{size?: string, coat?: string, price?: int, duration_minutes?: int}> $variations */
        $variations = $service->variations;

        foreach ($variations as $variation) {
            if (($variation['size'] ?? null) === $petSize && ($variation['coat'] ?? null) === $petCoat) {
                return $variation;
            }
        }

        return null;
    }
}
