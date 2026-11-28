<?php

declare(strict_types=1);

namespace App\Services;

/**
 * A not-yet-confirmed email change: the user the change applies to, the new
 * address awaiting confirmation, and the hash of the cancel token that was
 * issued alongside it to the current address (#95). The cancel hash ties the
 * cancellation link to exactly the change it accompanied: a stale link can
 * never cancel a newer request.
 */
final class PendingEmailChange
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly string $cancelHash,
    ) {}
}
