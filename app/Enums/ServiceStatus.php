<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ServiceStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Archived = 'archived';

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Archived => 'gray',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Attivo',
            self::Archived => 'Archiviato',
        };
    }
}
