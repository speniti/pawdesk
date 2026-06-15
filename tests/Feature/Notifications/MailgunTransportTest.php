<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;

test('tenant-mailgun transport is registered and resolves to Mailgun transport', function () {
    config()->set('mail.mailers.tenant-mailgun-test', [
        'transport' => 'tenant-mailgun',
        'secret' => 'test-key',
        'domain' => 'test.example.com',
    ]);

    $mailer = Mail::mailer('tenant-mailgun-test');
    $transport = $mailer->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(Symfony\Component\Mailer\Transport\AbstractTransport::class);
});

test('tenant-mailgun DSN defaults to the US endpoint', function () {
    config()->set('mail.mailers.tenant-mailgun-us-test', [
        'transport' => 'tenant-mailgun',
        'secret' => 'test-key',
        'domain' => 'us.example.com',
    ]);

    $mailer = Mail::mailer('tenant-mailgun-us-test');
    $transport = $mailer->getSymfonyTransport();

    expect((string) $transport)
        ->toContain('mailgun')
        ->toContain('api.mailgun.net')
        ->not->toContain('api.eu.mailgun.net');
});

test('tenant-mailgun DSN uses the EU endpoint when region is eu', function () {
    config()->set('mail.mailers.tenant-mailgun-eu-test', [
        'transport' => 'tenant-mailgun',
        'secret' => 'test-key',
        'domain' => 'eu.example.com',
        'region' => 'eu',
    ]);

    $mailer = Mail::mailer('tenant-mailgun-eu-test');
    $transport = $mailer->getSymfonyTransport();

    expect((string) $transport)->toContain('api.eu.mailgun.net');
});
