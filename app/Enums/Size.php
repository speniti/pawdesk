<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Size: string implements HasColor, HasLabel
{
    case Giant = 'giant';
    case Large = 'large';
    case Medium = 'medium';
    case Small = 'small';
    case Toy = 'toy';

    public function getColor(): string
    {
        return match ($this) {
            self::Toy => 'gray',
            self::Small => 'success',
            self::Medium => 'info',
            self::Large => 'warning',
            self::Giant => 'danger',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Toy => 'Toy',
            self::Small => 'Piccolo',
            self::Medium => 'Medio',
            self::Large => 'Grande',
            self::Giant => 'Gigante',
        };
    }
}
