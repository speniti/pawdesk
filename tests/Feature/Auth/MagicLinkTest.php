<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\MagicLinkConfirmation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MagicLinkService;
use Livewire\Livewire;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

describe('issueLink', function () {
    test('returns a verification URL with a long random token', function () {
        $user = User::factory()->admin()->create();

        $url = app(MagicLinkService::class)->issueLink($user);

        $token = (string) str($url)->after('/auth/magic-link/');

        expect($token)->toHaveLength(64)
            ->and($token)->not->toBe((string) str(app(MagicLinkService::class)->issueLink($user))->after('/auth/magic-link/'));
    });
});

describe('confirmation page', function () {
    test('shows a Filament confirmation page without consuming the token or authenticating', function () {
        $user = User::factory()->admin()->create();
        $url = app(MagicLinkService::class)->issueLink($user);

        get($url)->assertOk()->assertSee("Conferma l'accesso");

        assertGuest();

        Livewire::test(
            MagicLinkConfirmation::class,
            ['token' => (string) str($url)->after('/auth/magic-link/')]
        )
            ->call('confirm')
            ->assertRedirect();

        assertAuthenticatedAs($user);
    });

    test('redirects to login with an error when the token is unknown', function () {
        get('/auth/magic-link/'.str()->random(64))
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedine uno nuovo.');
    });

    test('redirects to login with an error when the token is expired', function () {
        $user = User::factory()->admin()->create();
        $url = app(MagicLinkService::class)->issueLink($user);

        $this->travel(16)->minutes();

        get($url)
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedine uno nuovo.');
    });
});

describe('link consumption', function () {
    test('logs in a user with a tenant and redirects to the tenant dashboard', function () {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->admin()->create();
        $user->tenants()->attach($tenant);
        $url = app(MagicLinkService::class)->issueLink($user);

        Livewire::test(MagicLinkConfirmation::class, ['token' => (string) str($url)->after('/auth/magic-link/')])
            ->call('confirm')
            ->assertRedirect("/$tenant->slug");

        assertAuthenticatedAs($user);
    });

    test('logs in a user without tenants and redirects to the panel root', function () {
        $user = User::factory()->admin()->create();
        $url = app(MagicLinkService::class)->issueLink($user);

        Livewire::test(MagicLinkConfirmation::class, ['token' => (string) str($url)->after('/auth/magic-link/')])
            ->call('confirm')
            ->assertRedirect('/');

        assertAuthenticatedAs($user);
    });

    test('marks the email as verified on first login', function () {
        $user = User::factory()->admin()->unverified()->create();
        $url = app(MagicLinkService::class)->issueLink($user);

        Livewire::test(MagicLinkConfirmation::class, ['token' => (string) str($url)->after('/auth/magic-link/')])
            ->call('confirm');

        expect($user->refresh()->email_verified_at)->not->toBeNull();
    });

    test('a consumed token cannot be used again', function () {
        $user = User::factory()->admin()->create();
        $url = app(MagicLinkService::class)->issueLink($user);
        $token = (string) str($url)->after('/auth/magic-link/');

        Livewire::test(MagicLinkConfirmation::class, ['token' => $token])
            ->call('confirm')
            ->assertRedirect();
        auth()->logout();

        Livewire::test(MagicLinkConfirmation::class, ['token' => $token])
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedine uno nuovo.');

        assertGuest();
    });

    test('an expired token cannot be consumed', function () {
        $user = User::factory()->admin()->create();
        $url = app(MagicLinkService::class)->issueLink($user);

        $this->travel(16)->minutes();

        Livewire::test(MagicLinkConfirmation::class, ['token' => (string) str($url)->after('/auth/magic-link/')])
            ->assertRedirect('/login');

        assertGuest();
    });
});
