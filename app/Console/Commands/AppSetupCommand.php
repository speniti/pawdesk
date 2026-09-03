<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

#[Signature('app:setup {--tenant=PawDesk : Tenant name} {--slug= : Tenant slug (URL identifier)} {--name=Admin : Admin user full name} {--email= : Admin user email}')]
#[Description('Create a tenant and an admin user, and associate them')]
class AppSetupCommand extends Command
{
    public function handle(): int
    {
        $email = $this->option('email');

        if ($email === null || $email === '') {
            $this->error('The --email option is required.');

            return self::FAILURE;
        }

        $tenantName = $this->option('tenant');
        $slug = $this->option('slug') ?: Str::slug($tenantName);

        $tenant = Tenant::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $tenantName,
                'notification_settings' => [],
                'opening_hours' => [],
                'settings' => [],
            ],
        );

        $this->info("Tenant: {$tenant->name} (slug: {$tenant->slug})");

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $this->option('name'),
                'role' => UserRole::Admin,
            ],
        );

        $tenant->users()->syncWithoutDetaching([$user->id]);

        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Role: {$user->role->value}");

        if ($user->wasRecentlyCreated) {
            $url = app(MagicLinkService::class)->issueLink($user);

            $this->info('Magic link (one-time use, valid for '.MagicLinkService::TTL_MINUTES.' minutes):');
            $this->info($url);

            try {
                $user->notify(new MagicLinkNotification($url));

                $this->info("Magic link also sent via email (mailer: {$this->laravel['config']['mail.default']}).");
            } catch (Throwable $exception) {
                $this->warn("Could not email the magic link (mailer: {$this->laravel['config']['mail.default']}): {$exception->getMessage()}");
            }
        }

        $this->info('Tenant and admin user are ready.');

        return self::SUCCESS;
    }
}
