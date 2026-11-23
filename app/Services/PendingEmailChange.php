<?php

declare(strict_types=1);

namespace App\Services;

/**
 * A not-yet-confirmed email change: the user the change applies to and the
 * new address awaiting confirmation.
 */
final class PendingEmailChange
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {}
}
