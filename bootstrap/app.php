<?php

declare(strict_types=1);

use App\Support\TrustedProxyConfig;
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
        // nothing and keeps single-tenant dev/CI behavior unchanged. The
        // parsing and the catch-all guard live in TrustedProxyConfig::fromEnv().
        $middleware->trustProxies(
            at: TrustedProxyConfig::fromString((string) env('TRUSTED_PROXIES')),
            headers: Request::HEADER_X_FORWARDED_TRAEFIK,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
