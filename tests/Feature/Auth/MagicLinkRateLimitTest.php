<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\get;

// Rate-limit counters rely on the array cache being rebuilt fresh for every
// test (CACHE_STORE=array in phpunit.xml): if that ever changes, reset state
// explicitly with RateLimiter::clear() in a beforeEach hook.
function requestFromIp(string $ip): Request
{
    return Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => $ip]);
}

test('magic-link requests are limited per email even when every request comes from a different ip', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();

    $page = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->instance();

    foreach (range(1, 5) as $attempt) {
        $this->app->instance('request', requestFromIp("203.0.113.{$attempt}"));

        $page->authenticate();
    }

    $this->app->instance('request', requestFromIp('198.51.100.77'));

    $page->authenticate();

    Notification::assertSentTo($user, MagicLinkNotification::class, 5);
});

test('a fresh email is not throttled because another email exhausted its own limit', function () {
    Notification::fake();

    $victim = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    $page = Livewire::test(Login::class)
        ->fillForm(['email' => $victim->email])
        ->instance();

    foreach (range(1, 6) as $attempt) {
        $this->app->instance('request', requestFromIp("203.0.113.{$attempt}"));

        $page->authenticate();
    }

    $page = Livewire::test(Login::class)
        ->fillForm(['email' => $other->email])
        ->instance();

    $this->app->instance('request', requestFromIp('198.51.100.99'));

    $page->authenticate();

    Notification::assertSentTo($victim, MagicLinkNotification::class, 5);
    Notification::assertSentTo($other, MagicLinkNotification::class, 1);
});

test('the per-email limit outlives the per-ip window on the livewire send path', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();

    $component = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email]);

    foreach (range(1, 5) as $attempt) {
        $component->call('authenticate');
    }

    $this->travel(61)->seconds();

    $component->call('authenticate');

    Notification::assertSentTo($user, MagicLinkNotification::class, 5);
});

test('six authenticate attempts from the same ip only send five links and notify the throttle', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();

    $component = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email]);

    foreach (range(1, 6) as $attempt) {
        $component->call('authenticate');
    }

    Notification::assertSentTo($user, MagicLinkNotification::class, 5);

    FilamentNotification::assertNotified(__('filament-panels::auth/pages/login.notifications.throttled.title'));
});

test('the magic-link verification route is throttled to ten requests per minute per ip', function () {
    $uri = '/auth/magic-link/'.str()->random(64);

    foreach (range(1, 10) as $attempt) {
        get($uri)->assertRedirect('/login');
    }

    get($uri)->assertStatus(429);
});

test('a tripped per-email limit on an existing account logs a masked warning', function () {
    Notification::fake();
    Log::spy();

    $user = User::factory()->admin()->create();

    $page = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->instance();

    foreach (range(1, 5) as $attempt) {
        $this->app->instance('request', requestFromIp("203.0.113.{$attempt}"));

        $page->authenticate();
    }

    $this->app->instance('request', requestFromIp('198.51.100.77'));

    $page->authenticate();

    Notification::assertSentTo($user, MagicLinkNotification::class, 5);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('magic-link-email limiter tripped for existing account', ['email_sha1' => hash('sha1', $user->email)]);
});

test('a tripped per-email limit on an unknown email logs no warning', function () {
    Notification::fake();
    Log::spy();

    User::factory()->admin()->create();

    $page = Livewire::test(Login::class)
        ->fillForm(['email' => 'nessuno@example.com'])
        ->instance();

    foreach (range(1, 6) as $attempt) {
        $this->app->instance('request', requestFromIp("203.0.113.{$attempt}"));

        $page->authenticate();
    }

    Log::shouldNotHaveReceived('warning');
});

test('an unknown email is throttled identically and never triggers a mail', function () {
    Notification::fake();

    User::factory()->admin()->create();

    $page = Livewire::test(Login::class)
        ->fillForm(['email' => 'nessuno@example.com'])
        ->instance();

    foreach (range(1, 5) as $attempt) {
        $this->app->instance('request', requestFromIp("203.0.113.{$attempt}"));

        $page->authenticate();
    }

    $this->app->instance('request', requestFromIp('198.51.100.77'));

    $page->authenticate();

    FilamentNotification::assertNotified(__('filament-panels::auth/pages/login.notifications.throttled.title'));

    Notification::assertNothingSent();
});
