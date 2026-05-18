<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Coat;
use App\Enums\Gender;
use App\Enums\Size;
use App\Enums\Species;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'name', 'species', 'breed', 'sex', 'date_of_birth', 'size', 'coat', 'behavioral_notes', 'health_notes'])]
class Pet extends Model
{
    /** @use HasFactory<\Database\Factories\PetFactory> */
    use HasFactory;

    protected $attributes = [
        'sex' => 'unknown',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    protected function casts(): array
    {
        return [
            'species' => Species::class,
            'sex' => Gender::class,
            'date_of_birth' => 'date',
            'size' => Size::class,
            'coat' => Coat::class,
        ];
    }
}
