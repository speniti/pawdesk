<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasColor, HasLabel
{
    case F = 'f';
    case M = 'm';
    case Unknown = 'unknown';

    public function getColor(): string
    {
        return match ($this) {
            Gender::M => 'info',
            Gender::F => 'pink',
            Gender::Unknown => 'gray',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            Gender::M => 'Maschio',
            Gender::F => 'Femmina',
            Gender::Unknown => 'Sconosciuto',
        };
    }
}
