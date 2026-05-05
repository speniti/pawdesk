<?php

declare(strict_types=1);

namespace App\Enums;

enum Size: string
{
    case Toy = 'toy';
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Giant = 'giant';
}
