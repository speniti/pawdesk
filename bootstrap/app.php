<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        // In production the app sits behind Traefik/Dokploy, so the real
        // client IP only arrives via X-Forwarded-* headers. Those headers are
        // honored exclusively when the direct peer belongs to one of the
        // configured proxy networks: an empty or unset TRUSTED_PROXIES trusts
        // nothing and keeps single-tenant dev/CI behavior unchanged.
        $trustedProxies = array_values(array_filter(array_map(trim(...), explode(',', (string) env('TRUSTED_PROXIES')))));

        // A catch-all CIDR would make every peer a trusted proxy, letting any
        // client pick its own IP via X-Forwarded-For: reject it loudly at boot
        // instead of silently reintroducing the spoofing this prevents.
        if (array_intersect($trustedProxies, ['0.0.0.0/0', '::/0']) !== []) {
            throw new RuntimeException(
                'TRUSTED_PROXIES must not contain the catch-all CIDRs 0.0.0.0/0 or ::/0: '
                .'every peer would become a trusted proxy and clients could spoof their IP. '
                .'List only your reverse-proxy networks (e.g. the Dokploy/Traefik docker network).',
            );
        }

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: Request::HEADER_X_FORWARDED_TRAEFIK,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
