<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ServiceCategory: string implements HasColor, HasLabel
{
    case Bath = 'bath';
    case Grooming = 'grooming';
    case Specialty = 'specialty';
    case Trimming = 'trimming';
    case Wellness = 'wellness';

    public function getColor(): string
    {
        return match ($this) {
            self::Grooming => 'primary',
            self::Bath => 'info',
            self::Trimming => 'warning',
            self::Wellness => 'success',
            self::Specialty => 'danger',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Grooming => 'Grooming',
            self::Bath => 'Bagno',
            self::Trimming => 'Tosatura',
            self::Wellness => 'Benessere',
            self::Specialty => 'Specialità',
        };
    }
}
