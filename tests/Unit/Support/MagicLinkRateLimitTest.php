<?php

declare(strict_types=1);

use App\Support\MagicLinkRateLimit;
use Illuminate\Http\Request;

test('limits by the normalized target email at five attempts per hour', function () {
    $limit = MagicLinkRateLimit::fromEmail('  Victim@Example.COM ', '203.0.113.9');

    expect($limit->key)->toBe('email:victim@example.com')
        ->and($limit->maxAttempts)->toBe(5)
        ->and($limit->decaySeconds)->toBe(3600);
});

test('falls back to the requester ip when no email is given', function () {
    expect(MagicLinkRateLimit::fromEmail(null, '203.0.113.9'))
        ->key->toBe('ip:203.0.113.9')
        ->and(MagicLinkRateLimit::fromEmail('   ', '203.0.113.9'))
        ->key->toBe('ip:203.0.113.9');
});

test('resolves the limit from the request body email', function () {
    $request = Request::create('/', 'POST', ['email' => 'Person@Example.com']);

    expect(MagicLinkRateLimit::fromRequest($request))
        ->key->toBe('email:person@example.com');
});
