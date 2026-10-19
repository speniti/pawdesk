<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Coat: string implements HasColor, HasLabel
{
    case Curly = 'curly';
    case DoubleCoat = 'double_coat';
    case Long = 'long';
    case Primitive = 'primitive';
    case Satin = 'satin';
    case Short = 'short';
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
            self::Smooth => 'Liscio',
            self::Satin => 'Raso',
            self::Long => 'Lungo',
            self::Curly => 'Riccio',
            self::Spaniel => 'Spaniel',
            self::DoubleCoat => 'Doppio pelo',
            self::Primitive => 'Primitivo',
        };
    }
}
