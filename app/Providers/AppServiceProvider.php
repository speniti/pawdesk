<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        URL::forceHttps(true);
    }

    public function register(): void
    {
        if ($this->app->environment('testing')) {
            URL::forceHttps(false);
        }
    }
}
