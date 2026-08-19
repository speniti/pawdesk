<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationStatus: string implements HasColor, HasLabel
{
    case Failed = 'failed';
    case Pending = 'pending';
    case Sent = 'sent';
    case Skipped = 'skipped';

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Skipped => 'warning',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'In attesa',
            self::Sent => 'Inviata',
            self::Failed => 'Fallita',
            self::Skipped => 'Saltata',
        };
    }
}
