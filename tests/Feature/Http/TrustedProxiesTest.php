<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

function defineClientIpRoute(): void
{
    Route::get('/_testing/client-ip', fn () => response((string) request()->ip()));
}

afterEach(function () {
    unset($_ENV['TRUSTED_PROXIES']);

    $this->refreshApplication();
});

test('a forwarded client ip is used when the request comes from a trusted proxy cidr', function () {
    $_ENV['TRUSTED_PROXIES'] = '172.18.0.0/16';
    $this->refreshApplication();
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('each cidr in a comma-separated TRUSTED_PROXIES list is honored', function () {
    $_ENV['TRUSTED_PROXIES'] = ' 172.18.0.0/16 , 10.0.0.0/8 ';
    $this->refreshApplication();
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '10.42.0.7']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('the whole proxy chain forwarded by traefik is walked back to the real client', function () {
    $_ENV['TRUSTED_PROXIES'] = '172.18.0.0/16';
    $this->refreshApplication();
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9, 172.18.0.9'])
        ->assertOk()
        ->assertSee('203.0.113.9');
});

test('forwarded headers are ignored when the direct peer is not a trusted proxy', function () {
    $_ENV['TRUSTED_PROXIES'] = '172.18.0.0/16';
    $this->refreshApplication();
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('198.51.100.7');
});

test('forwarded headers are ignored when no proxy is configured', function () {
    defineClientIpRoute();

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5']);

    get('/_testing/client-ip', ['X-Forwarded-For' => '203.0.113.9'])
        ->assertOk()
        ->assertSee('172.18.0.5');
});
