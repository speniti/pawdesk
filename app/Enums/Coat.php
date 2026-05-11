<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Coat: string implements HasColor, HasLabel
{
    case Curly = 'curly';
    case DoubleCoat = 'double_coat';
    case Feathered = 'feathered';
    case Flat = 'flat';
    case Long = 'long';
    case Primitive = 'primitive';
    case Short = 'short';
    case ShortHair = 'short_hair';
    case Smooth = 'smooth';
    case Spaniel = 'spaniel';

    public function getColor(): string
    {
        return 'gray';
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Short => 'Corto',
            self::ShortHair => 'Pelo corto',
            self::Smooth => 'Liscio',
            self::Flat => 'Piatto',
            self::Long => 'Lungo',
            self::Feathered => 'Piumato',
            self::Curly => 'Riccio',
            self::Spaniel => 'Spaniel',
            self::DoubleCoat => 'Doppio pelo',
            self::Primitive => 'Primitivo',
        };
    }
}
