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

test('a catch-all entry is rejected with a clear message', function (string $catchAll) {
    expect(fn () => TrustedProxyConfig::fromString($catchAll))
        ->toThrow(RuntimeException::class, 'TRUSTED_PROXIES must not contain catch-all entries');
})->with([
    'exact ipv4 catch-all' => ['0.0.0.0/0'],
    'exact ipv6 catch-all' => ['::/0'],
    'long-form ipv6 catch-all' => ['0:0:0:0:0:0:0:0/0'],
    'ipv4-mapped ipv6 catch-all' => ['::ffff:0.0.0.0/0'],
    'netmask zero on a private address' => ['1.2.3.4/0'],
    'netmask zero with inner whitespace' => ['0.0.0.0/ 0'],
    'uppercase cidr' => ['::FFFF:0.0.0.0/0'],
    'remote_addr literal' => ['REMOTE_ADDR'],
    'remote_addr lowercase' => ['remote_addr'],
    'private_ranges literal' => ['private_ranges'],
]);

test('a catch-all entry mixed into a valid list is rejected', function () {
    expect(fn () => TrustedProxyConfig::fromString('10.0.0.0/8, 0.0.0.0/0'))
        ->toThrow(RuntimeException::class, 'TRUSTED_PROXIES');
});

test('a private cidr is not mistaken for a catch-all', function () {
    expect(TrustedProxyConfig::fromString('10.0.0.0/8, 172.18.0.0/16, 192.168.0.0/24'))
        ->toBe(['10.0.0.0/8', '172.18.0.0/16', '192.168.0.0/24']);
});

test('a /16 or /8 netmask is not mistaken for a catch-all', function () {
    expect(TrustedProxyConfig::fromString('172.18.0.0/16'))
        ->toBe(['172.18.0.0/16'])
        ->and(TrustedProxyConfig::fromString('10.0.0.0/8'))
        ->toBe(['10.0.0.0/8']);
});
