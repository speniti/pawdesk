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
        return $this->isAdmin($user) && $this->isMemberOfCurrentTenant($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $user->isNot($model)
            && $this->belongsToCurrentTenant($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user) && $this->isMemberOfCurrentTenant($user);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $user->isNot($model)
            && $this->belongsToCurrentTenant($user, $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $this->belongsToCurrentTenant($user, $model);
    }

    public function update(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $this->belongsToCurrentTenant($user, $model);
    }

    public function view(User $user, User $model): bool
    {
        return $this->isAdmin($user)
            && $this->belongsToCurrentTenant($user, $model);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) && $this->isMemberOfCurrentTenant($user);
    }

    /**
     * Users carry no tenant_id column: both memberships are checked on the
     * pivot — the actor's (#92) and the target user's, which is what scopes
     * the record itself.
     */
    protected function belongsToCurrentTenant(User $user, User $model): bool
    {
        if (! $tenant = Filament::getTenant()) {
            return false;
        }

        return $user->canAccessTenant($tenant)
            && $model->tenants()->whereKey($tenant)->exists();
    }
}
