<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'primary_color' => fake()->hexColor(),
            'opening_hours' => [
                'monday' => [['open' => '09:00', 'close' => '18:00']],
                'tuesday' => [['open' => '09:00', 'close' => '18:00']],
                'wednesday' => [['open' => '09:00', 'close' => '18:00']],
                'thursday' => [['open' => '09:00', 'close' => '18:00']],
                'friday' => [['open' => '09:00', 'close' => '18:00']],
                'saturday' => [['open' => '09:00', 'close' => '13:00']],
                'sunday' => [],
            ],
            'notification_settings' => [],
            'settings' => [
                'slot_duration_minutes' => 30,
                'buffer_minutes' => 15,
            ],
        ];
    }
}
