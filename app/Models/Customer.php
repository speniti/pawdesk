<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PreferredChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'first_name',
    'last_name',
    'email',
    'phone',
    'address',
    'preferred_channel',
    'gdpr_policy_sent_at',
    'marketing_consent_at',
    'preferences',
    'notes',
])]
class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
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
            'preferred_channel' => PreferredChannel::class,
            'gdpr_policy_sent_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => mb_trim("$this->first_name $this->last_name"),
        );
    }
}
