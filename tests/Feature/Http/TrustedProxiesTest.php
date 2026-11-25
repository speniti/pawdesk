<?php

declare(strict_types=1);

use App\Support\TrustedProxyConfig;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

function defineClientIpRoute(): void
{
    Route::get('/_testing/client-ip', fn () => response((string) request()->ip()));
}

function configureTrustedProxies(array $proxies): void
{
    // Configure the middleware exactly as the bootstrap closure does with
    // TrustedProxyConfig::fromString((string) env('TRUSTED_PROXIES')) and
    // HEADER_X_FORWARDED_TRAEFIK on a real boot. The closure itself fires on
    // the first build of the HTTP kernel per container, and Dotenv re-writes
    // the .env value into $_SERVER/$_ENV on every boot (its Env repository is
    // a process-wide static), so an env override set before
    // refreshApplication() is clobbered before the closure ever reads it.
    // Driving the statics directly keeps these tests deterministic: the env
    // -> middleware wiring is covered by the bootstrap test below, parsing
    // and the catch-all guard by the TrustedProxyConfig unit tests.
    TrustProxies::flushState();
    TrustProxies::at($proxies);
    TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_TRAEFIK);
}

function trustedProxiesStatic(string $property): mixed
{
    $propertyReflection = new ReflectionProperty(TrustProxies::class, $property);
    $propertyReflection->setAccessible(true);

    return $propertyReflection->getValue();
}

beforeEach(function () {
    TrustProxies::flushState();
});

afterEach(function () {
    TrustProxies::flushState();
});

test('the bootstrap wires TRUSTED_PROXIES into the middleware', function () {
    // A fresh boot fires the withMiddleware closure, so after
    // refreshApplication() the middleware statics must match exactly what
    // the closure computes from the environment. Asserting against env() at
    // assert time keeps the expectation self-consistent whatever the local
    // .env contains (CI ships an empty TRUSTED_PROXIES= and expects []).
    $this->refreshApplication();

    expect(trustedProxiesStatic('alwaysTrustProxies'))
        ->toBe(TrustedProxyConfig::fromString((string) env('TRUSTED_PROXIES')))
        ->and(trustedProxiesStatic('alwaysTrustHeaders'))
        ->toBe(Request::HEADER_X_FORWARDED_TRAEFIK);
});

test('a forwarded client ip is used when the request comes from a trusted proxy cidr', function () {
    configureTrustedProxies(['172.18.0.0/16']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('each cidr in a comma-separated TRUSTED_PROXIES list is honored', function () {
    configureTrustedProxies(['172.18.0.0/16', '10.0.0.0/8']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '10.42.0.7']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('the whole proxy chain forwarded by traefik is walked back to the real client', function () {
    configureTrustedProxies(['172.18.0.0/16']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9, 172.18.0.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('forwarded headers are ignored when the direct peer is not a trusted proxy', function () {
    configureTrustedProxies(['172.18.0.0/16']);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('198.51.100.7');
});

test('forwarded headers are ignored when no proxy is configured', function () {
    configureTrustedProxies([]);
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('172.18.0.5');
});
