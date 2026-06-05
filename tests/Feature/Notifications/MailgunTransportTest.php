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

test('tenant-mailgun DSN defaults to EU endpoint', function () {
    config()->set('mail.mailers.tenant-mailgun-eu-test', [
        'transport' => 'tenant-mailgun',
        'secret' => 'test-key',
        'domain' => 'eu.example.com',
    ]);

    $mailer = Mail::mailer('tenant-mailgun-eu-test');
    $transport = $mailer->getSymfonyTransport();

    expect((string) $transport)->toContain('mailgun');
});
