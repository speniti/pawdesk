<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Treatment;
use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;

class TreatmentPolicy
{
    use InteractsWithRoles;

    /**
     * Treatments are system-generated on appointment completion,
     * they cannot be created manually.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Treatments are system-generated on appointment completion,
     * they cannot be deleted.
     */
    public function delete(User $user, Treatment $treatment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Treatment $treatment): bool
    {
        return false;
    }

    public function restore(User $user, Treatment $treatment): bool
    {
        return false;
    }

    public function update(User $user, Treatment $treatment): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($treatment);
    }

    public function view(User $user, Treatment $treatment): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($treatment);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }
}
