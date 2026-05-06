<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case NoShow = 'no_show';
    case Requested = 'requested';
}
