<?php

declare(strict_types=1);

namespace App\Enums;

enum PreferredChannel: string
{
    case Email = 'email';
    case Whatsapp = 'whatsapp';
    case Sms = 'sms';
}
