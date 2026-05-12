<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait InteractsWithRoles
{
    protected function isAdmin(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    protected function isStaffOrAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff], true);
    }
}
