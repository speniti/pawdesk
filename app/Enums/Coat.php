<?php

declare(strict_types=1);

namespace App\Enums;

enum Coat: string
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
}
