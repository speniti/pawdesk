<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InpsMode: string implements HasLabel
{
    case Fixed = 'fixed';

    case Percentage = 'percentage';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Contributi fissi (artigiani/commercianti)',
            self::Percentage => 'Percentuale sull\'imponibile (gestione separata)',
        };
    }
}
