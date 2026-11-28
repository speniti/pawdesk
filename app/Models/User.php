<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * @property UserRole $role
 */
#[Fillable(['name', 'email', 'role'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int|string, bool> */
    private array $tenantAccessCache = [];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public function canAccessTenant(Model $tenant): bool
    {
        // Policies call this on every ability check and Filament authorizes
        // table actions per row: memoize per instance so the membership
        // query runs once per user+tenant pair per request however many
        // checks hit it (#92).
        return $this->tenantAccessCache[$tenant->getKey()]
            ??= $this->tenants()->whereKey($tenant)->exists();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants()->get();
    }

    public function hasTenantsOtherThan(Tenant $tenant): bool
    {
        return $this->tenants()
            ->whereKeyNot($tenant)
            ->exists();
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }
}
