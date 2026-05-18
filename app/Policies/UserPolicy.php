<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;
use Filament\Facades\Filament;

class UserPolicy
{
    use InteractsWithRoles;

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $user->isNot($model)
            && $this->belongsToCurrentTenant($model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $user->isNot($model)
            && $this->belongsToCurrentTenant($model);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $this->belongsToCurrentTenant($model);
    }

    public function update(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $this->belongsToCurrentTenant($model);
    }

    public function view(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $this->belongsToCurrentTenant($model);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    protected function belongsToCurrentTenant(User $model): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }

        return $model->tenants()->whereKey($tenant)->exists();
    }
}
