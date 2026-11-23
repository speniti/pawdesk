<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

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
