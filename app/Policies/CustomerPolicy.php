<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;

class CustomerPolicy
{
    use InteractsWithRoles;

    public function create(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }
}
