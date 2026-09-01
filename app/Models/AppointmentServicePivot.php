<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $appointment_id
 * @property int $service_id
 * @property int $applied_price
 * @property int $duration_minutes
 */
#[Fillable(['applied_price', 'duration_minutes'])]
class AppointmentServicePivot extends Pivot
{
    protected function casts(): array
    {
        return [
            'applied_price' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }
}
