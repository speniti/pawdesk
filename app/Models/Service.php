<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Coat;
use App\Enums\ServiceCategory;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'description',
    'category',
    'coat',
    'duration_minutes',
    'base_price',
    'combinable',
    'status',
    'size_prices',
])]
class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $attributes = [
        'combinable' => true,
        'status' => 'active',
        'size_prices' => '{}',
    ];

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_service')
            ->withPivot(['applied_price', 'duration_minutes']);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'category' => ServiceCategory::class,
            'coat' => Coat::class,
            'combinable' => 'boolean',
            'status' => ServiceStatus::class,
            'size_prices' => 'array',
        ];
    }
}
