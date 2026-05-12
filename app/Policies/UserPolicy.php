<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;

class UserPolicy
{
    use InteractsWithRoles;

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->isAdmin($user);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
