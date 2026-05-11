<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Species: string implements HasColor, HasLabel
{
    case Cat = 'cat';
    case Dog = 'dog';
    case Other = 'other';

    public function getColor(): string
    {
        return match ($this) {
            self::Dog => 'info',
            self::Cat => 'warning',
            self::Other => 'gray',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Dog => 'Cane',
            self::Cat => 'Gatto',
            self::Other => 'Altro',
        };
    }
}
