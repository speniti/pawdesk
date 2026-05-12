<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppointmentStatus: string implements HasColor, HasLabel
{
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case NoShow = 'no_show';
    case Requested = 'requested';

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'gray',
            self::Confirmed => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::NoShow => 'danger',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Richiesto',
            self::Confirmed => 'Confermato',
            self::InProgress => 'In corso',
            self::Completed => 'Completato',
            self::Cancelled => 'Annullato',
            self::NoShow => 'Non presentato',
        };
    }
}
