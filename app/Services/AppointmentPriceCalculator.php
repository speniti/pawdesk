<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Size;
use App\Models\Service;
use Illuminate\Support\Collection;

class AppointmentPriceCalculator
{
    /**
     * @param  Collection<int, Service>  $services
     * @param  Size|string|null  $petSize
     * @return array<int, array{applied_price: int, duration_minutes: int}>
     */
    public static function buildPivotData(Collection $services, Size|string|null $petSize): array
    {
        return $services->mapWithKeys(fn (Service $service) => [
            $service->id => [
                'applied_price' => self::resolvePrice($service, $petSize),
                'duration_minutes' => $service->duration_minutes,
            ],
        ])->all();
    }

    /**
     * @param  Size|string|null  $petSize
     */
    public static function resolvePrice(Service $service, Size|string|null $petSize): int
    {
        $petSizeValue = $petSize instanceof Size ? $petSize->value : $petSize;

        if ($petSizeValue !== null) {
            /** @var array<array{size?: string, price?: int}> $sizePrices */
            $sizePrices = $service->size_prices;

            foreach ($sizePrices as $entry) {
                if (($entry['size'] ?? null) === $petSizeValue) {
                    return (int) ($entry['price'] ?? $service->base_price);
                }
            }
        }

        return $service->base_price;
    }
}
