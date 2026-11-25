<?php

declare(strict_types=1);

use App\Support\TrustedProxyConfig;

test('parses a single cidr', function () {
    expect(TrustedProxyConfig::fromString('172.18.0.0/16'))
        ->toBe(['172.18.0.0/16']);
});

test('parses a comma separated list and trims whitespace', function () {
    expect(TrustedProxyConfig::fromString(' 172.18.0.0/16 , 10.0.0.0/8 '))
        ->toBe(['172.18.0.0/16', '10.0.0.0/8']);
});

test('an empty value trusts nothing', function () {
    expect(TrustedProxyConfig::fromString(''))->toBe([]);
});

test('only whitespace trusts nothing', function () {
    expect(TrustedProxyConfig::fromString('  '))->toBe([]);
});

test('a catch-all cidr is rejected with a clear message', function (string $catchAll) {
    expect(fn () => TrustedProxyConfig::fromString($catchAll))
        ->toThrow(RuntimeException::class, 'TRUSTED_PROXIES must not contain the catch-all');
})->with([
    '0.0.0.0/0' => ['0.0.0.0/0'],
    '::/0' => ['::/0'],
]);

test('a catch-all cidr mixed into a valid list is rejected', function () {
    expect(fn () => TrustedProxyConfig::fromString('10.0.0.0/8, 0.0.0.0/0'))
        ->toThrow(RuntimeException::class, 'TRUSTED_PROXIES');
});
