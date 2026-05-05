<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'appointment_id',
    'customer_id',
    'pet_id',
    'actual_duration_minutes',
    'final_price',
    'notes',
    'visible_to_customer',
    'products_used',
])]
class Treatment extends Model
{
    /** @use HasFactory<\Database\Factories\TreatmentFactory> */
    use HasFactory;

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'actual_duration_minutes' => 'integer',
            'final_price' => 'integer',
            'visible_to_customer' => 'boolean',
        ];
    }
}
