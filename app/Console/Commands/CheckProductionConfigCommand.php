<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-production-config')]
#[Description('Fail if debug mode is enabled in production')]
class CheckProductionConfigCommand extends Command
{
    public function handle(): int
    {
        // After bootstrap, isProduction() is config-derived (LoadConfiguration
        // rebinds env from config, cached config included), so this check uses
        // exactly the same inputs the framework uses to render verbose errors.
        // In this stack that is sound: bootstrap/cache is not persisted and
        // AUTORUN's optimize rebuilds the cache from the compose-forced env at
        // every container start (#73).
        if (! $this->laravel->isProduction() || ! config('app.debug')) {
            return self::SUCCESS;
        }

        $this->error('APP_DEBUG is enabled in production: verbose stack traces would leak secrets. Set APP_DEBUG=0 and redeploy.');

        return self::FAILURE;
    }
}
