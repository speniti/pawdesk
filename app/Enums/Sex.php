<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Sex: string implements HasColor, HasLabel
{
    case F = 'f';
    case M = 'm';
    case Unknown = 'unknown';

    public function getColor(): string
    {
        return match ($this) {
            self::M => 'info',
            self::F => 'pink',
            self::Unknown => 'gray',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::M => 'Maschio',
            self::F => 'Femmina',
            self::Unknown => 'Sconosciuto',
        };
    }
}
