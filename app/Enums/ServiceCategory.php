<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ServiceCategory: string implements HasLabel
{
    case Bath = 'bath';
    case Grooming = 'grooming';
    case Specialty = 'specialty';
    case Trimming = 'trimming';
    case Wellness = 'wellness';

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
