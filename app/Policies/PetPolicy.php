<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pet;
use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;

class PetPolicy
{
    use InteractsWithRoles;

    public function create(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function delete(User $user, Pet $pet): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function forceDelete(User $user, Pet $pet): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function restore(User $user, Pet $pet): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function update(User $user, Pet $pet): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function view(User $user, Pet $pet): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }
}
