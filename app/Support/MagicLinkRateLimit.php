<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

/**
 * Rate limit for outgoing magic-link emails, keyed by the target address so
 * that an attacker rotating IPs cannot keep mailing the same victim inbox.
 */
final class MagicLinkRateLimit
{
    /** Length of the window, in hours. */
    public const int DECAY_HOURS = 1;

    /** Maximum magic-link emails per target inbox per window. */
    public const int MAX_ATTEMPTS = 5;

    /**
     * Limit for a magic-link request aimed at the given email address. When no
     * email is provided (or it is blank), the limit falls back to the
     * requester IP so the request can never bypass throttling entirely.
     */
    public static function fromEmail(?string $email, string $ip): Limit
    {
        $email = mb_strtolower(mb_trim((string) $email));

        return Limit::perHour(self::MAX_ATTEMPTS, self::DECAY_HOURS)
            ->by($email !== '' ? 'email:'.$email : 'ip:'.$ip);
    }

    /**
     * Limit resolved from the request: the email is read from the request
     * input, with the IP as fallback key.
     */
    public static function fromRequest(Request $request): Limit
    {
        return self::fromEmail($request->input('email'), (string) $request->ip());
    }
}
