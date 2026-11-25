<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Issues and consumes single-use confirmation tokens for email changes, in
 * the same style as the login magic links: a 64-character random token whose
 * sha256 hash is stored in the cache for a short TTL. Only one pending change
 * exists per user; issuing a new one replaces the previous.
 */
class PendingEmailService
{
    public const int TTL_MINUTES = 15;

    public function consume(string $token): ?PendingEmailChange
    {
        // The pull-then-forget sequence is not atomic: a concurrent confirm of
        // the same token may leave the stale per-user index behind (harmless —
        // same semantics as MagicLinkService; the token itself stays consumed).
        $pending = Cache::pull($this->tokenKey($token));

        if (! $pending instanceof PendingEmailChange) {
            return null;
        }

        $userKey = $this->userKey($pending->userId);

        if (Cache::get($userKey) === hash('sha256', $token)) {
            Cache::forget($userKey);
        }

        return $pending;
    }

    public function issueChange(User $user, string $newEmail): string
    {
        $token = Str::random(64);
        $hash = hash('sha256', $token);

        // The two-key replace is not atomic: a concurrent request may orphan
        // one token entry, which simply expires with its TTL.
        $previousHash = Cache::pull($this->userKey($user->id));

        if (is_string($previousHash)) {
            Cache::forget($this->tokenKeyFromHash($previousHash));
        }

        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        Cache::put($this->tokenKeyFromHash($hash), new PendingEmailChange($user->id, $newEmail), $expiresAt);
        Cache::put($this->userKey($user->id), $hash, $expiresAt);

        return route(Filament::getDefaultPanel()->generateRouteName('auth.email-change.verify'), $token);
    }

    public function isValid(string $token): bool
    {
        return Cache::has($this->tokenKey($token));
    }

    /**
     * The new address awaiting confirmation for the given user, if any.
     */
    public function pendingEmailFor(User $user): ?string
    {
        $hash = Cache::get($this->userKey($user->id));

        if (! is_string($hash)) {
            return null;
        }

        $pending = Cache::get($this->tokenKeyFromHash($hash));

        return $pending instanceof PendingEmailChange ? $pending->email : null;
    }

    private function tokenKey(string $token): string
    {
        return $this->tokenKeyFromHash(hash('sha256', $token));
    }

    private function tokenKeyFromHash(string $hash): string
    {
        return 'pending-email:token:'.$hash;
    }

    private function userKey(int $userId): string
    {
        return 'pending-email:user:'.$userId;
    }
}
