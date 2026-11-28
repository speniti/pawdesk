<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Issues and consumes single-use tokens for email changes, in the same style
 * as the login magic links: a 64-character random token whose sha256 hash is
 * stored in the cache for a short TTL. Only one pending change exists per
 * user; issuing a new one replaces the previous.
 *
 * Every request produces two tokens (#95): the confirmation token sent to
 * the NEW address and the cancellation token sent to the CURRENT one, so the
 * legitimate owner can undo a change they did not request. Cancelling kills
 * the confirmation token and confirming kills the cancellation token; a
 * replacement request invalidates both previous tokens.
 */
class PendingEmailService
{
    public const int TTL_MINUTES = 15;

    public function cancelByToken(string $token): bool
    {
        $hash = hash('sha256', $token);
        $userId = Cache::pull($this->cancelKeyFromHash($hash));

        if (! is_int($userId)) {
            return false;
        }

        $userKey = $this->userKey($userId);
        $confirmHash = Cache::get($userKey);
        $pending = is_string($confirmHash)
            ? Cache::get($this->tokenKeyFromHash($confirmHash))
            : null;

        // A link only ever cancels the change it accompanied. If the current
        // pending change (if any) was issued later, its cancel hash differs:
        // this link is stale, the newer request stays untouched.
        if (! $pending instanceof PendingEmailChange || $pending->cancelHash !== $hash) {
            return false;
        }

        Cache::forget($userKey);
        Cache::forget($this->tokenKeyFromHash($confirmHash));

        return true;
    }

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

        // The change is applied: its cancellation link must die with it.
        Cache::forget($this->cancelKeyFromHash($pending->cancelHash));

        return $pending;
    }

    public function isCancelValid(string $token): bool
    {
        return Cache::has($this->cancelKey($token));
    }

    public function issueChange(User $user, string $newEmail): PendingEmailLinks
    {
        $token = Str::random(64);
        $cancelToken = Str::random(64);
        $hash = hash('sha256', $token);
        $cancelHash = hash('sha256', $cancelToken);

        // The multi-key replace is not atomic: a concurrent request may orphan
        // one token entry, which simply expires with its TTL.
        $previousHash = Cache::pull($this->userKey($user->id));

        if (is_string($previousHash)) {
            $previous = Cache::get($this->tokenKeyFromHash($previousHash));

            if ($previous instanceof PendingEmailChange) {
                Cache::forget($this->cancelKeyFromHash($previous->cancelHash));
            }

            Cache::forget($this->tokenKeyFromHash($previousHash));
        }

        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        Cache::put($this->tokenKeyFromHash($hash), new PendingEmailChange($user->id, $newEmail, $cancelHash), $expiresAt);
        Cache::put($this->userKey($user->id), $hash, $expiresAt);
        Cache::put($this->cancelKeyFromHash($cancelHash), $user->id, $expiresAt);

        $panel = Filament::getDefaultPanel();

        return new PendingEmailLinks(
            confirmUrl: route($panel->generateRouteName('auth.email-change.verify'), $token),
            cancelUrl: route($panel->generateRouteName('auth.email-change.cancel'), $cancelToken),
        );
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

    private function cancelKey(string $token): string
    {
        return $this->cancelKeyFromHash(hash('sha256', $token));
    }

    private function cancelKeyFromHash(string $hash): string
    {
        return 'pending-email:cancel:'.$hash;
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
