<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class TrustedProxyConfig
{
    private const CATCH_ALL_CIDRS = ['0.0.0.0/0', '::/0'];

    /**
     * Parse a raw TRUSTED_PROXIES value into a list of CIDRs.
     *
     * A catch-all CIDR would make every peer a trusted proxy, letting any
     * client pick its own IP via X-Forwarded-For: it is rejected loudly
     * instead of silently reintroducing the spoofing this prevents.
     *
     * @return list<string>
     */
    public static function fromString(string $trustedProxies): array
    {
        $proxies = array_values(array_filter(array_map(
            trim(...),
            explode(',', $trustedProxies),
        )));

        if (array_intersect($proxies, self::CATCH_ALL_CIDRS) !== []) {
            throw new RuntimeException(
                'TRUSTED_PROXIES must not contain the catch-all CIDRs 0.0.0.0/0 or ::/0: '
                .'every peer would become a trusted proxy and clients could spoof their IP. '
                .'List only your reverse-proxy networks (e.g. the Dokploy/Traefik docker network).',
            );
        }

        return $proxies;
    }
}
