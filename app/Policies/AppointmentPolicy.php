<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Policies\Concerns\InteractsWithRoles;

class AppointmentPolicy
{
    use InteractsWithRoles;

    public function create(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($appointment);
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($appointment);
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($appointment);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($appointment);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->isStaffOrAdmin($user) && $this->belongsToCurrentTenant($appointment);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }
}
