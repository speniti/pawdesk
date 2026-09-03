<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

test('login with a registered email sends a magic link', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertNotified('Link inviato');

    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('login with an unknown email shows the same generic message and sends nothing', function () {
    Notification::fake();

    User::factory()->admin()->create();

    Livewire::test(Login::class)
        ->fillForm(['email' => 'nessuno@example.com'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertNotified('Link inviato');

    Notification::assertNothingSent();
});

test('login email is required', function () {
    User::factory()->admin()->create();

    Livewire::test(Login::class)
        ->fillForm(['email' => ''])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);
});

test('login is rate limited after five attempts', function () {
    Notification::fake();

    $user = User::factory()->admin()->create();

    $component = Livewire::test(Login::class)
        ->fillForm(['email' => $user->email]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $component->call('authenticate');
    }

    $component->call('authenticate');

    Notification::assertSentTo($user, MagicLinkNotification::class, 5);
});

test('logout destroys session', function () {
    actingAs(User::factory()->admin()->create());

    post('/logout')->assertRedirect('/login');

    assertGuest();
});
