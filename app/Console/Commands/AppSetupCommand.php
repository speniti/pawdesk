<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:setup {--tenant=PawDesk : Tenant name} {--slug= : Tenant slug (URL identifier)} {--name=Admin : Admin user full name} {--email= : Admin user email} {--password= : Admin user password}')]
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

        $password = $this->option('password');

        if ($password === null || $password === '') {
            if ($this->option('no-interaction')) {
                $this->error('The --password option is required when running in no-interaction mode.');

                return self::FAILURE;
            }

            $password = $this->secret('What is the admin password?');

            if ($password === null || $password === '') {
                $this->error('A password is required.');

                return self::FAILURE;
            }
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
                'password' => $password,
                'role' => UserRole::Admin,
            ],
        );

        $tenant->users()->syncWithoutDetaching([$user->id]);

        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Role: {$user->role->value}");
        $this->info('Tenant and admin user are ready.');

        return self::SUCCESS;
    }
}
