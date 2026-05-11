<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PreferredChannel: string implements HasColor, HasLabel
{
    case Email = 'email';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';

    public function getColor(): string
    {
        return match ($this) {
            self::Email => 'info',
            self::Sms => 'warning',
            self::Whatsapp => 'success',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Whatsapp => 'WhatsApp',
        };
    }
}
