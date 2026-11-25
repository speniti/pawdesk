<?php

declare(strict_types=1);

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

function defineClientIpRoute(): void
{
    Route::get('/_testing/client-ip', fn () => response((string) request()->ip()));
}

function actAsProxyConfiguration(array $proxies): void
{
    // Configure the middleware exactly as the bootstrap closure does with
    // TrustedProxyConfig::fromEnv() and HEADER_X_FORWARDED_TRAEFIK on a real
    // boot. The closure itself is wired via afterResolving(HttpKernel::class)
    // and in a test process fires only once (Livewire resolves the contract
    // one-shot), so refreshApplication() cannot re-run it deterministically:
    // the parsing and the catch-all guard are covered by the
    // TrustedProxyConfig unit tests instead.
    TrustProxies::flushState();
    TrustProxies::at($proxies);
    TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_TRAEFIK);
}

beforeEach(function () {
    TrustProxies::flushState();
});

test('a forwarded client ip is used when the request comes from a trusted proxy cidr', function () {
    actAsProxyConfiguration(['172.18.0.0/16']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('each cidr in a comma-separated TRUSTED_PROXIES list is honored', function () {
    actAsProxyConfiguration(['172.18.0.0/16', '10.0.0.0/8']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '10.42.0.7']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('the whole proxy chain forwarded by traefik is walked back to the real client', function () {
    actAsProxyConfiguration(['172.18.0.0/16']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9, 172.18.0.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('forwarded headers are ignored when the direct peer is not a trusted proxy', function () {
    actAsProxyConfiguration(['172.18.0.0/16']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('198.51.100.7');
});

test('forwarded headers are ignored when no proxy is configured', function () {
    actAsProxyConfiguration([]);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('172.18.0.5');
});
