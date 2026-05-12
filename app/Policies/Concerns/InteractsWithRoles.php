<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithRoles
{
    protected function belongsToCurrentTenant(Model $model): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null && $model->tenant_id === $tenant->getKey();
    }

    protected function isAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    protected function isStaffOrAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff], true);
    }
}
