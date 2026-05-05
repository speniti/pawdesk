<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
