<?php

declare(strict_types=1);

namespace App\Enums;

enum Size: string
{
    case Giant = 'giant';
    case Large = 'large';
    case Medium = 'medium';
    case Small = 'small';
    case Toy = 'toy';
}
