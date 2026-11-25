<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class TrustedProxyConfig
{
    /**
     * Entry spellings that make every peer a trusted proxy, letting any
     * client pick its own IP via X-Forwarded-For. Compared normalized
     * (lowercase, all whitespace stripped) so equivalents like
     * "0.0.0.0/ 0" or "REMOTE_ADDR" cannot slip through.
     */
    private const CATCH_ALL_ENTRIES = [
        'remote_addr',       // Laravel replaces it with the live peer IP
        'private_ranges',    // not a proxy list: would silently trust nothing
        'private_subnets',
    ];

    /**
     * Parse a raw TRUSTED_PROXIES value into a list of CIDRs.
     *
     * A catch-all entry is rejected loudly at boot instead of silently
     * reintroducing the spoofing this prevents. Both exact spellings
     * (0.0.0.0/0, ::/0) and functional equivalents are rejected: any CIDR
     * with a /0 netmask matches every address, and REMOTE_ADDR makes the
     * live peer trusted.
     *
     * @return list<string>
     */
    public static function fromString(string $trustedProxies): array
    {
        $proxies = array_values(array_filter(array_map(
            trim(...),
            explode(',', $trustedProxies),
        )));

        $catchAll = array_filter($proxies, self::isCatchAll(...));

        if ($catchAll !== []) {
            throw new RuntimeException(
                'TRUSTED_PROXIES must not contain catch-all entries (any /0 netmask, '
                .'or the REMOTE_ADDR / private_ranges literals): every peer would become '
                .'a trusted proxy and clients could spoof their IP. '
                .'List only your reverse-proxy networks (e.g. the Dokploy/Traefik docker network).',
            );
        }

        return $proxies;
    }

    private static function isCatchAll(string $proxy): bool
    {
        $normalized = mb_strtolower(preg_replace('/\s+/', '', $proxy));

        if (str_ends_with($normalized, '/0')) {
            return true;
        }

        return in_array($normalized, self::CATCH_ALL_ENTRIES, true);
    }
}
