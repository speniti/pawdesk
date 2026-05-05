# Multi-Tenancy & Filament Panel Setup — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement Filament native multi-tenancy with a `Tenant` model, `BelongsToTenant` trait with automatic global scope, and panel configuration for tenant resolution.

**Architecture:** Filament's many-to-many tenancy pattern where users belong to tenants via a pivot table. A `BelongsToTenant` trait applies an automatic global scope to all tenant-scoped models, ensuring data isolation. Tenant resolution is handled by Filament's built-in `HasTenants` interface on the User model.

**Tech Stack:** Laravel 13, Filament 5, Pest 4, SQLite

---

### Task 1: Create Tenants Migration

**Files:**
- Create: `database/migrations/2026_05_05_000001_create_tenants_table.php`

**Step 1: Create the migration file**

Run: `php artisan make:migration create_tenants_table --no-interaction`

Then replace the generated file content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('primary_color', 7)->nullable();
            $table->json('opening_hours')->default('{}');
            $table->text('notification_settings');
            $table->json('settings')->default('{}');
            $table->timestamp('created_at', precision: 0)->useCurrent();
            $table->timestamp('updated_at', precision: 0)->useCurrent();

            $table->index('slug');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
```

**Step 2: Run migration to verify**

Run: `php artisan migrate --no-interaction`
Expected: `Migration completed successfully.` with no errors.

**Step 3: Commit**

```bash
git add database/migrations/2026_05_05_000001_create_tenants_table.php
git commit -m "feat: add tenants migration for multi-tenancy"
```

---

### Task 2: Create Tenant Model with Factory

**Files:**
- Create: `app/Models/Tenant.php`
- Create: `database/factories/TenantFactory.php`

**Step 1: Write the failing test**

Create `tests/Feature/TenantModelTest.php`:

```php
<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

test('Tenant has correct fillable attributes', function () {
    $tenant = new Tenant;

    expect($tenant->getFillable())->toBe([
        'name',
        'slug',
        'primary_color',
        'opening_hours',
        'notification_settings',
        'settings',
    ]);
});

test('Tenant casts opening_hours and settings as array', function () {
    $tenant = new Tenant;
    $casts = $tenant->getCasts();

    expect($casts)->toHaveKey('opening_hours', 'array');
    expect($casts)->toHaveKey('settings', 'array');
});

test('Tenant casts notification_settings as encrypted', function () {
    $tenant = new Tenant;
    $casts = $tenant->getCasts();

    expect($casts)->toHaveKey('notification_settings', 'encrypted');
});

test('Tenant factory creates valid tenant', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->name)->not->toBeEmpty();
    expect($tenant->slug)->not->toBeEmpty();
    expect($tenant->slug)->toBe(\Illuminate\Support\Str::slug($tenant->name));
    expect($tenant->opening_hours)->toBeArray();
    expect($tenant->settings)->toBeArray();
});

test('Tenant encrypts and decrypts notification_settings', function () {
    $settings = ['mailgun_api_key' => 'key-test123'];
    $tenant = Tenant::factory()->create(['notification_settings' => $settings]);
    $tenant->refresh();

    expect($tenant->notification_settings)->toBe($settings);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TenantModelTest`
Expected: FAIL — `Class "App\Models\Tenant" not found`

**Step 3: Create Tenant model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'primary_color', 'opening_hours', 'notification_settings', 'settings'])]
class Tenant extends Model
{
    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'notification_settings' => 'encrypted',
            'settings' => 'array',
        ];
    }
}
```

**Step 4: Create TenantFactory**

Create `database/factories/TenantFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'primary_color' => '#' . fake()->hexColor(),
            'opening_hours' => [],
            'notification_settings' => [],
            'settings' => [],
        ];
    }
}
```

**Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=TenantModelTest`
Expected: 5 PASS

**Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 7: Commit**

```bash
git add app/Models/Tenant.php database/factories/TenantFactory.php tests/Feature/TenantModelTest.php
git commit -m "feat: add Tenant model with encrypted casts and factory"
```

---

### Task 3: Add tenant_id to Users Table

**Files:**
- Create: `database/migrations/2026_05_05_000002_add_tenant_id_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`

**Step 1: Write the failing test**

Add to `tests/Feature/UserModelTest.php` (add at end of file):

```php
test('User belongs to tenant', function () {
    $tenant = \App\Models\Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    expect($user->tenant)->toBeInstanceOf(\App\Models\Tenant::class);
    expect($user->tenant->id)->toBe($tenant->id);
});

test('User has role attribute', function () {
    $user = User::factory()->create(['role' => 'admin']);

    expect($user->role)->toBe('admin');
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=UserModelTest`
Expected: FAIL — column `tenant_id` not found

**Step 3: Create migration to add tenant_id to users**

Run: `php artisan make:migration add_tenant_id_to_users_table --no-interaction`

Replace generated file content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff')->after('password');

            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'role']);
        });
    }
};
```

**Step 4: Update User model**

Replace `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

**Step 5: Update UserFactory**

Update `database/factories/UserFactory.php` to include `tenant_id` and `role`:

```php
<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'staff',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
```

**Step 6: Update existing User tests to account for tenant_id requirement**

Update `tests/Feature/UserModelTest.php` — adjust the `fillable` and `casts` tests:

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('User has correct fillable attributes', function () {
    $user = new User;

    expect($user->getFillable())->toBe(['name', 'email', 'password', 'tenant_id', 'role']);
});

test('User hides sensitive attributes', function () {
    $user = new User;

    expect($user->getHidden())->toBe(['password', 'remember_token']);
});

test('User casts email_verified_at as datetime and password as hashed', function () {
    $user = new User;
    $casts = $user->getCasts();

    expect($casts)->toHaveKey('email_verified_at', 'datetime');
    expect($casts)->toHaveKey('password', 'hashed');
});

test('User factory creates valid user', function () {
    $user = User::factory()->create();

    expect($user->name)->not->toBeEmpty();
    expect($user->email)->not->toBeEmpty();
    expect($user->email)->toContain('@');
    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->tenant)->not->toBeNull();
    expect($user->role)->toBe('staff');
});

test('User belongs to tenant', function () {
    $tenant = \App\Models\Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    expect($user->tenant)->toBeInstanceOf(\App\Models\Tenant::class);
    expect($user->tenant->id)->toBe($tenant->id);
});

test('User has role attribute', function () {
    $user = User::factory()->create(['role' => 'admin']);

    expect($user->role)->toBe('admin');
});
```

**Step 7: Run migration and tests**

Run: `php artisan migrate --no-interaction`
Run: `php artisan test --compact --filter=UserModelTest`
Expected: 6 PASS

**Step 8: Update RoutingTest and DatabaseSeeder for tenant-aware User**

The existing routing tests and seeder need updating because Users now require a `tenant_id`. Update `tests/Feature/RoutingTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('/up health check responds 200', function () {
    $this->get('/up')->assertSuccessful();
});

test('/admin redirects to login when unauthenticated', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('/admin dashboard responds 200 when authenticated', function () {
    Config::set('app.env', 'local');

    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertSuccessful();
});
```

(No change needed here since `User::factory()->create()` now auto-creates a Tenant via the factory relationship.)

Update `database/seeders/DatabaseSeeder.php` — the seed user now needs a tenant:

```php
<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'PawDesk Demo',
            'slug' => 'pawdesk-demo',
        ]);

        User::factory()->create([
            'name' => 'Simone Peniti',
            'email' => 'simone@peniti.it',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'tenant_id' => $tenant->id,
        ]);
    }
}
```

**Step 9: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 10: Run full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS

**Step 11: Commit**

```bash
git add -A
git commit -m "feat: add tenant_id and role to users, update factory and seeder"
```

---

### Task 4: Create tenant_user Pivot Table and Implement HasTenants

**Files:**
- Create: `database/migrations/2026_05_05_000003_create_tenant_user_table.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Tenant.php`

Filament's multi-tenancy requires a many-to-many relationship between User and Tenant (a user can belong to multiple tenants). We need a pivot table and the `HasTenants` interface on User.

**Step 1: Write the failing test**

Create `tests/Feature/TenancyTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;

test('User implements HasTenants interface', function () {
    $user = new User;

    expect($user)->toBeInstanceOf(\Filament\Models\Contracts\HasTenants::class);
});

test('User implements FilamentUser interface', function () {
    $user = new User;

    expect($user)->toBeInstanceOf(\Filament\Models\Contracts\FilamentUser::class);
});

test('User getTenants returns tenants the user belongs to', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->tenants()->attach($tenant);

    $panel = Filament::getPanel('admin');
    $tenants = $user->getTenants($panel);

    expect($tenants->count())->toBe(1);
    expect($tenants->first()->id)->toBe($tenant->id);
});

test('User canAccessTenant returns true for associated tenant', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->tenants()->attach($tenant);

    expect($user->canAccessTenant($tenant))->toBeTrue();
});

test('User canAccessTenant returns false for non-associated tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant1->id]);
    $user->tenants()->attach($tenant1);

    expect($user->canAccessTenant($tenant2))->toBeFalse();
});

test('Tenant has many users', function () {
    $tenant = Tenant::factory()->create();
    $user1 = User::factory()->create(['tenant_id' => $tenant->id]);
    $user2 = User::factory()->create(['tenant_id' => $tenant->id]);
    $tenant->users()->attach([$user1->id, $user2->id]);

    expect($tenant->users()->count())->toBe(2);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TenancyTest`
Expected: FAIL — `Class "App\Models\Tenant"` relationship or interface not implemented

**Step 3: Create pivot migration**

Run: `php artisan make:migration create_tenant_user_table --no-interaction`

Replace generated content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
```

**Step 4: Update User model with HasTenants and FilamentUser**

Replace `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants()->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }
}
```

**Step 5: Update Tenant model with users relationship**

Update `app/Models/Tenant.php` — add `users()` relationship:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'primary_color', 'opening_hours', 'notification_settings', 'settings'])]
class Tenant extends Model
{
    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'notification_settings' => 'encrypted',
            'settings' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
```

**Step 6: Run migration and tests**

Run: `php artisan migrate --no-interaction`
Run: `php artisan test --compact --filter=TenancyTest`
Expected: 6 PASS

**Step 7: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 8: Commit**

```bash
git add -A
git commit -m "feat: add tenant_user pivot and implement HasTenants on User"
```

---

### Task 5: Configure Filament Panel for Multi-Tenancy

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

**Step 1: Write the failing test**

Add to `tests/Feature/TenancyTest.php`:

```php
test('Filament admin panel has tenancy configured', function () {
    $panel = Filament::getPanel('admin');
    $tenantModel = $panel->getTenantModel();

    expect($tenantModel)->not->toBeNull();
    expect($tenantModel)->toBe(\App\Models\Tenant::class);
});

test('Filament admin panel tenant slug attribute is slug', function () {
    $panel = Filament::getPanel('admin');
    $slugAttribute = $panel->getTenantSlugAttribute();

    expect($slugAttribute)->toBe('slug');
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=TenancyTest`
Expected: FAIL on new tests — tenancy not configured

**Step 3: Update AdminPanelProvider**

Replace `app/Providers/Filament/AdminPanelProvider.php`:

```php
<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->tenant(Tenant::class, slugAttribute: 'slug')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=TenancyTest`
Expected: ALL PASS (including new tests)

**Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 6: Commit**

```bash
git add app/Providers/Filament/AdminPanelProvider.php tests/Feature/TenancyTest.php
git commit -m "feat: configure Filament panel multi-tenancy with Tenant model"
```

---

### Task 6: Create BelongsToTenant Trait

**Files:**
- Create: `app/Models/Concerns/BelongsToTenant.php`

**Step 1: Write the failing test**

Create `tests/Unit/BelongsToTenantTraitTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;

test('BelongsToTenant trait applies tenant_id global scope', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
    $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

    Filament::setTenant($tenant1);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $users = User::all();

    expect($users)->toHaveCount(1);
    expect($users->first()->id)->toBe($user1->id);
});

test('BelongsToTenant trait automatically sets tenant_id on create', function () {
    $tenant = Tenant::factory()->create();

    Filament::setTenant($tenant);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $user = User::factory()->make(['tenant_id' => null]);
    $user->save();

    expect($user->tenant_id)->toBe($tenant->id);
});

test('BelongsToTenant trait does not override explicit tenant_id', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    Filament::setTenant($tenant1);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $user = User::factory()->create(['tenant_id' => $tenant2->id]);

    expect($user->tenant_id)->toBe($tenant2->id);
});
```

> **Note:** The `User` model already has `tenant_id` and a `tenant()` relationship, but does NOT yet use the `BelongsToTenant` trait. We will add it in Step 3.

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=BelongsToTenantTraitTest`
Expected: FAIL — trait not applied, all tenants' records returned

**Step 3: Create BelongsToTenant trait**

Create `app/Models/Concerns/BelongsToTenant.php`:

```php
<?php

namespace App\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $tenant = Filament::getTenant();

            if ($tenant !== null) {
                $query->where('tenant_id', $tenant->getKey());
            }
        });

        static::creating(function (Model $model) {
            if ($model->tenant_id === null) {
                $tenant = Filament::getTenant();

                if ($tenant !== null) {
                    $model->tenant_id = $tenant->getKey();
                }
            }
        });
    }
}
```

**Step 4: Apply trait to User model**

Add `use BelongsToTenant` to `app/Models/User.php`:

```php
use App\Models\Concerns\BelongsToTenant;
// ...
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use BelongsToTenant, HasFactory, Notifiable;
    // ...
}
```

**Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=BelongsToTenantTraitTest`
Expected: 3 PASS

**Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 7: Commit**

```bash
git add app/Models/Concerns/BelongsToTenant.php app/Models/User.php tests/Unit/BelongsToTenantTraitTest.php
git commit -m "feat: add BelongsToTenant trait with global scope and auto-assign"
```

---

### Task 7: Create ApplyTenantScopes Middleware

**Files:**
- Create: `app/Http/Middleware/ApplyTenantScopes.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

The `ApplyTenantScopes` middleware ensures that models without Filament resources are also scoped to the current tenant. This is Filament's recommended pattern for complete tenant isolation.

**Step 1: Write the failing test**

Add to `tests/Feature/TenancyTest.php`:

```php
test('ApplyTenantScopes middleware is registered on tenant routes', function () {
    $panel = Filament::getPanel('admin');
    $middleware = $panel->getTenantMiddleware();

    expect($middleware)->toContain(\App\Http\Middleware\ApplyTenantScopes::class);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="ApplyTenantScopes"`
Expected: FAIL — middleware not registered

**Step 3: Create middleware**

Run: `php artisan make:middleware ApplyTenantScopes --no-interaction`

Replace `app/Http/Middleware/ApplyTenantScopes.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApplyTenantScopes
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return $next($request);
        }

        User::addGlobalScope(
            'tenant',
            fn(Builder $query) => $query->whereBelongsTo($tenant),
        );

        return $next($request);
    }
}
```

**Step 4: Register middleware in AdminPanelProvider**

Add `tenantMiddleware()` call to `AdminPanelProvider::panel()`:

```php
->tenant(Tenant::class, slugAttribute: 'slug')
->tenantMiddleware([
    ApplyTenantScopes::class,
], isPersistent: true)
```

Add the import at top:

```php
use App\Http\Middleware\ApplyTenantScopes;
```

**Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter="ApplyTenantScopes"`
Expected: PASS

**Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 7: Commit**

```bash
git add app/Http/Middleware/ApplyTenantScopes.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/TenancyTest.php
git commit -m "feat: add ApplyTenantScopes middleware for complete tenant isolation"
```

---

### Task 8: Feature Test — Tenant Data Isolation

**Files:**
- Create: `tests/Feature/TenantIsolationTest.php`

This is a critical security test that verifies data from one tenant is never visible to another.

**Step 1: Write the test**

Create `tests/Feature/TenantIsolationTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;

test('users from tenant A are not visible in tenant B context', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    Filament::setTenant($tenantA);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $users = User::all();

    expect($users)->toHaveCount(1);
    expect($users->first()->id)->toBe($userA->id);
    expect($users->pluck('id'))->not->toContain($userB->id);
});

test('switching tenant context changes visible data', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    User::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
    User::factory()->count(5)->create(['tenant_id' => $tenantB->id]);

    // Context: Tenant A
    Filament::setTenant($tenantA);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    expect(User::count())->toBe(3);

    // Context: Tenant B
    Filament::setTenant($tenantB);
    Filament::bootCurrentPanel();

    expect(User::count())->toBe(5);
});

test('new records are automatically scoped to current tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    Filament::setTenant($tenant);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();

    $user = User::factory()->make(['tenant_id' => null]);
    $user->save();

    expect($user->tenant_id)->toBe($tenant->id);

    // Verify it's not visible in other tenant's context
    Filament::setTenant($otherTenant);
    Filament::bootCurrentPanel();

    expect(User::where('id', $user->id)->exists())->toBeFalse();
});
```

**Step 2: Run test to verify it passes**

Run: `php artisan test --compact --filter=TenantIsolationTest`
Expected: 3 PASS

**Step 3: Run full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS

**Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 5: Commit**

```bash
git add tests/Feature/TenantIsolationTest.php
git commit -m "test: add tenant data isolation feature tests"
```

---

### Task 9: Fix Existing Tests and Run Full Suite

**Files:**
- Possibly modify: `tests/Feature/ConfigurationTest.php`, `tests/Feature/RoutingTest.php`

After all changes, the existing tests may need adjustments because the panel now requires tenant context for authenticated routes.

**Step 1: Run full test suite**

Run: `php artisan test --compact`

**Step 2: Fix any failing tests**

If the routing test for `/admin dashboard responds 200 when authenticated` fails (because tenant is now required), update it:

```php
test('/admin dashboard responds 200 when authenticated', function () {
    Config::set('app.env', 'local');

    $user = User::factory()->create();

    $this->actingAs($user);

    // After login, Filament redirects to tenant selection
    // Accessing /admin directly should redirect to tenant context
    $this->get('/admin')
        ->assertSuccessful(); // Still works in local env without FilamentUser checks
});
```

Or if tenant context is required in the URL:

```php
test('/admin dashboard responds 200 when authenticated with tenant', function () {
    Config::set('app.env', 'local');

    $tenant = \App\Models\Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->tenants()->attach($tenant);

    $this->actingAs($user);

    $this->get("/admin/{$tenant->slug}")
        ->assertSuccessful();
});
```

**Step 3: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 4: Commit**

```bash
git add -A
git commit -m "fix: update existing tests for tenant-aware panel"
```

---

### Task 10: Final Verification

**Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS

**Step 2: Run pint on all files**

Run: `vendor/bin/pint --format agent`

**Step 3: Verify database schema**

Run: `php artisan db:show --counts`
Expected: Tables `tenants`, `users`, `tenant_user` exist with correct columns.

**Step 4: Verify Filament panel routes**

Run: `php artisan route:list --path=admin`
Expected: Tenant-aware routes visible (e.g., `/admin/{tenant}`).

---

## Summary of Files Created/Modified

| Action | File |
|--------|------|
| Create | `database/migrations/2026_05_05_000001_create_tenants_table.php` |
| Create | `database/migrations/2026_05_05_000002_add_tenant_id_to_users_table.php` |
| Create | `database/migrations/2026_05_05_000003_create_tenant_user_table.php` |
| Create | `app/Models/Tenant.php` |
| Create | `app/Models/Concerns/BelongsToTenant.php` |
| Create | `app/Http/Middleware/ApplyTenantScopes.php` |
| Create | `database/factories/TenantFactory.php` |
| Create | `tests/Feature/TenantModelTest.php` |
| Create | `tests/Feature/TenancyTest.php` |
| Create | `tests/Feature/TenantIsolationTest.php` |
| Create | `tests/Unit/BelongsToTenantTraitTest.php` |
| Modify | `app/Models/User.php` |
| Modify | `app/Providers/Filament/AdminPanelProvider.php` |
| Modify | `database/factories/UserFactory.php` |
| Modify | `database/seeders/DatabaseSeeder.php` |
| Modify | `tests/Feature/UserModelTest.php` |
| Possibly modify | `tests/Feature/RoutingTest.php` |
| Possibly modify | `tests/Feature/ConfigurationTest.php` |
