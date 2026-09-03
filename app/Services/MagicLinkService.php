<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MagicLinkService
{
    public const int TTL_MINUTES = 15;

    public function consume(string $token): ?User
    {
        if (! $userId = Cache::pull($this->cacheKey($token))) {
            return null;
        }

        return User::find($userId);
    }

    public function issueLink(User $user): string
    {
        $token = Str::random(64);

        Cache::put(
            $this->cacheKey($token),
            $user->id, now()->addMinutes(self::TTL_MINUTES)
        );

        return route(Filament::getDefaultPanel()->generateRouteName('auth.magic-link.verify'), $token);
    }

    public function isValid(string $token): bool
    {
        return Cache::has($this->cacheKey($token));
    }

    private function cacheKey(string $token): string
    {
        return 'magic-link:token:'.hash('sha256', $token);
    }
}
