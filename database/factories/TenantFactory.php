<?php

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
            'primary_color' => '#' . str_pad(dechex(fake()->numberBetween(0, 16777215)), 6, '0', STR_PAD_LEFT),
            'opening_hours' => [],
            'notification_settings' => [],
            'settings' => [],
        ];
    }
}
