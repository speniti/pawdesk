<?php

declare(strict_types=1);

namespace App\Enums;

enum Species: string
{
    case Cat = 'cat';
    case Dog = 'dog';
    case Other = 'other';
}
