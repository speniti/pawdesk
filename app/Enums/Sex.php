<?php

declare(strict_types=1);

namespace App\Enums;

enum Sex: string
{
    case M = 'm';
    case F = 'f';
    case Unknown = 'unknown';
}
