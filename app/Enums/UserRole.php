<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Staff = 'staff';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Admin => 'warning',
            self::Staff => 'info',
        };
    }

    public function getLabel(): string|null
    {
        return match ($this) {
            self::Admin => 'Amministratore',
            self::Staff => 'Operatore',
        };
    }
}
