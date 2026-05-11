<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff]);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff]);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff]);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff]);
    }

    public function update(User $user, Customer $customer): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff]);
    }

    public function view(User $user, Customer $customer): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff]);
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Staff]);
    }
}
