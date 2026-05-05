<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
