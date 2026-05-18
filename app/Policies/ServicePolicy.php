<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;

class ServicePolicy
{
    use InteractsWithRoles;

    public function create(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($service);
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($service);
    }

    public function restore(User $user, Service $service): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($service);
    }

    public function update(User $user, Service $service): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($service);
    }

    public function view(User $user, Service $service): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($service);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }
}
