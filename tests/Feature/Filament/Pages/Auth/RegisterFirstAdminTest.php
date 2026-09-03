<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RegisterFirstAdmin;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\assertGuest;

describe('availability', function () {
    test('renders when no admin exists', function () {
        User::factory()->create();

        Livewire::test(RegisterFirstAdmin::class)
            ->assertOk()
            ->assertDontSee(Filament::getLoginUrl());
    });

    test('returns 404 when an admin already exists', function () {
        User::factory()->admin()->create();

        Livewire::test(RegisterFirstAdmin::class)
            ->assertStatus(404);
    });

    test('register returns 404 when an admin is created after the page was loaded', function () {
        $component = Livewire::test(RegisterFirstAdmin::class)
            ->assertOk();

        User::factory()->admin()->create();

        $component
            ->fillForm([
                'name' => 'Simone Peniti',
                'email' => 'simone@peniti.it',
            ])
            ->call('register')
            ->assertStatus(404);

        expect(User::query()->count())->toBe(1);
    });
});

describe('registration', function () {
    test('creates the first admin, sends a magic link, and does not log them in', function () {
        Notification::fake();

        Livewire::test(RegisterFirstAdmin::class)
            ->fillForm([
                'name' => 'Simone Peniti',
                'email' => 'simone@peniti.it',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect(filament()->getLoginUrl());

        $user = User::query()->sole();

        expect($user->name)->toBe('Simone Peniti')
            ->and($user->role)->toBe(UserRole::Admin);

        assertGuest();

        Notification::assertSentTo($user, MagicLinkNotification::class);
    });

    test('required fields are validated', function () {
        Notification::fake();

        Livewire::test(RegisterFirstAdmin::class)
            ->fillForm([
                'name' => '',
                'email' => '',
            ])
            ->call('register')
            ->assertHasFormErrors(['name', 'email']);

        expect(User::query()->count())->toBe(0);
    });
});

describe('login redirect', function () {
    test('redirects to first admin registration when no admin exists', function () {
        Filament::setCurrentPanel(Filament::getDefaultPanel());

        Livewire::test(Login::class)
            ->assertRedirect('/register');
    });

    test('renders the form without a register link when an admin exists', function () {
        User::factory()->admin()->create();

        Filament::setCurrentPanel(Filament::getDefaultPanel());

        Livewire::test(Login::class)
            ->assertOk()
            ->assertDontSee(Filament::getRegistrationUrl());
    });
});
