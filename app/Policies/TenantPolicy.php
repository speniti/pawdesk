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
        return $this->isAdmin($user)
            && $user->tenants()->where('tenants.id', $tenant->id)->exists();
    }
}
