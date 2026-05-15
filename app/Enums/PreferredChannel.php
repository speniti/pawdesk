<?php

declare(strict_types=1);

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum PreferredChannel: string implements HasColor, HasIcon, HasLabel
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

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::Email => Heroicon::OutlinedEnvelope,
            self::Sms => Heroicon::OutlinedDevicePhoneMobile,
            self::Whatsapp => Heroicon::OutlinedChatBubbleLeftRight,
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
