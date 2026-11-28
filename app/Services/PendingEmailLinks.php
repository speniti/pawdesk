<?php

declare(strict_types=1);

namespace App\Services;

/**
 * The two single-use links an email change request produces: the confirmation
 * link for the NEW address and the cancellation link for the CURRENT one
 * (#95). The raw tokens exist only here — they cannot be reconstructed later.
 */
final class PendingEmailLinks
{
    public function __construct(
        public readonly string $confirmUrl,
        public readonly string $cancelUrl,
    ) {}
}
