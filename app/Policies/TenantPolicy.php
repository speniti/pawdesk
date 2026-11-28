<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;

class TenantPolicy
{
    use InteractsWithRoles;

    public function update(User $user, Tenant $tenant): bool
    {
        // The actor and the subject coincide here: canAccessTenant keeps a
        // single membership implementation and shares its memo (#92).
        return $this->isAdmin($user)
            && $user->canAccessTenant($tenant);
    }
}
