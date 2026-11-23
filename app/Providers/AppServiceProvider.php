<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TenantRevenue;
use App\Support\MagicLinkRateLimit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // Named limiter for the magic-link send path: Login::authenticate()
        // enforces it manually through MagicLinkRateLimit because the target
        // email lives in the Livewire form state, not in the HTTP request
        // input, and the manual enforcement does not share counters with this
        // named registration.
        RateLimiter::for(
            'magic-link-email',
            fn (Request $request): Limit => MagicLinkRateLimit::fromRequest($request),
        );

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
