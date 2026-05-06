<?php

declare(strict_types=1);

namespace App\Enums;

enum PreferredChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
}
