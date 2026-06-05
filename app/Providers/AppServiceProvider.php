<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        URL::forceHttps(! $this->app->environment('testing'));

        Mail::extend('tenant-mailgun', function (array $config = []) {
            return (new MailgunTransportFactory)->create(
                new Dsn(
                    'mailgun+https',
                    'hosted.eu.mailgun.org',
                    $config['secret'] ?? '',
                    $config['domain'] ?? '',
                )
            );
        });
    }
}
