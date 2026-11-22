<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TenantRevenue;
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
                    'default',
                    $config['secret'] ?? '',
                    $config['domain'] ?? '',
                    null,
                    ['region' => $config['region'] ?? 'us'],
                )
            );
        });
    }

    public function register(): void
    {
        // Scoped: one instance per request so the dashboard page, stats and
        // chart share the monthly revenue cache, without leaking it across
        // requests in long-lived workers.
        $this->app->scoped(TenantRevenue::class);
    }
}
