<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\EmailChangeConfirmation;
use App\Filament\Pages\Auth\Profile;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmailChangeConfirmationNotification;
use App\Notifications\EmailChangeNoticeNotification;
use App\Services\PendingEmailService;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

function requestEmailChangeViaProfile(User $user, Tenant $tenant, string $newEmail): string
{
    bootFilamentPanelAs($user, $tenant);

    Livewire::test(Profile::class)
        ->fillForm(['email' => $newEmail])
        ->call('save')
        ->assertNotified();

    $pendingEmail = app(PendingEmailService::class)->pendingEmailFor($user);

    expect($pendingEmail)->toBe($newEmail);

    $url = '';

    Notification::assertSentTo(
        Notification::route('mail', $newEmail),
        EmailChangeConfirmationNotification::class,
        function (EmailChangeConfirmationNotification $notification) use (&$url): bool {
            $url = $notification->url;

            return str_starts_with($url, url('/auth/email-change/'));
        },
    );

    return $url;
}

beforeEach(function () {
    Notification::fake();

    $this->tenant = Tenant::factory()->create();

    $this->user = User::factory()->admin()->forTenant($this->tenant)->create(['email' => 'vecchio@example.com']);
});

describe('email change request', function () {
    test('does not persist the new email and notifies both addresses', function () {
        $url = requestEmailChangeViaProfile($this->user, $this->tenant, 'nuovo@example.com');

        expect($url)->toStartWith(url('/auth/email-change/'))
            ->and($this->user->refresh()->email)->toBe('vecchio@example.com')
            ->and((string) str($url)->after('/auth/email-change/'))->toHaveLength(64);

        Notification::assertSentTo($this->user, EmailChangeNoticeNotification::class, 1);
        Notification::assertNotSentTo($this->user, EmailChangeConfirmationNotification::class);
        Notification::assertNotSentTo(
            Notification::route('mail', 'nuovo@example.com'),
            EmailChangeNoticeNotification::class,
        );
    });

    test('a case-variant of the current address is a normal save', function () {
        bootFilamentPanelAs($this->user, $this->tenant);

        Livewire::test(Profile::class)
            ->fillForm(['email' => 'VECCHIO@example.com'])
            ->call('save')
            ->assertNotified();

        expect($this->user->refresh()->email)->toBe('vecchio@example.com')
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBeNull();

        Notification::assertNothingSent();
    });

    test('still saves the other profile fields and keeps the form on the current email', function () {
        bootFilamentPanelAs($this->user, $this->tenant);

        Livewire::test(Profile::class)
            ->fillForm(['name' => 'Nome Aggiornato', 'email' => 'nuovo@example.com'])
            ->call('save')
            ->assertNotified();

        expect($this->user->refresh()->name)->toBe('Nome Aggiornato')
            ->and($this->user->refresh()->email)->toBe('vecchio@example.com');

        Livewire::test(Profile::class)
            ->assertFormSet(['email' => 'vecchio@example.com']);
    });

    test('a save with the same email is a normal save and sends nothing', function () {
        bootFilamentPanelAs($this->user, $this->tenant);

        Livewire::test(Profile::class)
            ->fillForm(['name' => 'Stesso Nome', 'email' => 'vecchio@example.com'])
            ->call('save')
            ->assertNotified();

        expect($this->user->refresh()->name)->toBe('Stesso Nome')
            ->and($this->user->refresh()->email)->toBe('vecchio@example.com');

        Notification::assertNothingSent();
    });

    test('an email already taken by another user fails validation at request time', function () {
        User::factory()->create(['email' => 'occupata@example.com']);

        bootFilamentPanelAs($this->user, $this->tenant);

        Livewire::test(Profile::class)
            ->fillForm(['email' => 'occupata@example.com'])
            ->call('save')
            ->assertHasFormErrors(['email']);

        expect($this->user->refresh()->email)->toBe('vecchio@example.com');

        Notification::assertNothingSent();
    });

    test('a new pending request replaces the previous one', function () {
        $firstUrl = requestEmailChangeViaProfile($this->user, $this->tenant, 'primo@example.com');
        $secondUrl = requestEmailChangeViaProfile($this->user, $this->tenant, 'secondo@example.com');

        expect($firstUrl)->not->toBe($secondUrl)
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBe('secondo@example.com');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($firstUrl)->after('/auth/email-change/')])
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');

        expect($this->user->refresh()->email)->toBe('vecchio@example.com');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($secondUrl)->after('/auth/email-change/')])
            ->call('confirm')
            ->assertRedirect();

        expect($this->user->refresh()->email)->toBe('secondo@example.com');
    });

    test('the profile shows a pending change notice while awaiting confirmation', function () {
        requestEmailChangeViaProfile($this->user, $this->tenant, 'nuovo@example.com');

        bootFilamentPanelAs($this->user, $this->tenant);

        Livewire::test(Profile::class)
            ->assertSee('in attesa di conferma')
            ->assertSee('nuovo@example.com')
            ->assertFormSet(['email' => 'vecchio@example.com']);
    });
});

describe('confirmation page', function () {
    test('shows a confirmation page without consuming the token or authenticating', function () {
        $url = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        get($url)->assertOk()->assertSee("Conferma il cambio dell'email");

        assertGuest();

        expect($this->user->refresh()->email)->toBe('vecchio@example.com');

        Livewire::test(
            EmailChangeConfirmation::class,
            ['token' => (string) str($url)->after('/auth/email-change/')]
        )
            ->call('confirm')
            ->assertRedirect();

        expect($this->user->refresh()->email)->toBe('nuovo@example.com');
    });

    test('redirects to login with an error when the token is unknown', function () {
        get('/auth/email-change/'.str()->random(64))
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');
    });

    test('redirects to login with an error when the token is expired', function () {
        $url = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        $this->travel(16)->minutes();

        get($url)
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');
    });
});

describe('link consumption', function () {
    test('applies the change for a guest visitor without authenticating them', function () {
        $url = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($url)->after('/auth/email-change/')])
            ->call('confirm')
            ->assertRedirect('/login');

        expect($this->user->refresh()->email)->toBe('nuovo@example.com');

        assertGuest();
    });

    test('marks the new email as verified when it is confirmed', function () {
        $this->user->forceFill(['email_verified_at' => null])->save();

        $url = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($url)->after('/auth/email-change/')])
            ->call('confirm');

        expect($this->user->refresh()->email_verified_at)->not->toBeNull();
    });

    test('a consumed token cannot be used again', function () {
        $url = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');
        $token = (string) str($url)->after('/auth/email-change/');

        $component = Livewire::test(EmailChangeConfirmation::class, ['token' => $token]);

        $component->call('confirm')->assertRedirect();

        expect($this->user->refresh()->email)->toBe('nuovo@example.com');

        // Attack shape: a fresh pending change is issued for the account, then
        // the stale confirmation page is clicked again — it must not consume
        // the new pending change nor leak anything beyond the generic failure.
        app(PendingEmailService::class)->issueChange($this->user, 'terzo@example.com');

        $component->call('confirm')
            ->assertRedirect()
            ->assertNotified("Non è stato possibile applicare il cambio dell'email.");

        expect($this->user->refresh()->email)->toBe('nuovo@example.com')
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBe('terzo@example.com');
    });

    test('an expired token cannot be consumed', function () {
        $url = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        $component = Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($url)->after('/auth/email-change/')]);

        $this->travel(16)->minutes();

        $component->call('confirm')->assertRedirect();

        expect($this->user->refresh()->email)->toBe('vecchio@example.com')
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBeNull();
    });

    test('an email claimed by another user in the meantime is rejected cleanly', function () {
        $url = app(PendingEmailService::class)->issueChange($this->user, 'contesa@example.com');
        $token = (string) str($url)->after('/auth/email-change/');

        User::factory()->create(['email' => 'contesa@example.com']);

        Livewire::test(EmailChangeConfirmation::class, ['token' => $token])
            ->call('confirm')
            ->assertRedirect()
            ->assertNotified("Non è stato possibile applicare il cambio dell'email.");

        expect($this->user->refresh()->email)->toBe('vecchio@example.com')
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBeNull();
    });

    test('a pending change for a deleted user fails generically', function () {
        $url = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        $component = Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($url)->after('/auth/email-change/')]);

        $this->user->delete();

        $component->call('confirm')
            ->assertRedirect()
            ->assertNotified("Non è stato possibile applicare il cambio dell'email.");

        expect(User::count())->toBe(0);
    });
});

describe('notification content', function () {
    test('the confirmation mail contains the link and never the current address', function () {
        $url = url('/auth/email-change/'.str()->random(64));

        $rendered = (string) (new EmailChangeConfirmationNotification($url))->toMail($this->user)->render();

        expect($rendered)->toContain($url)
            ->and($rendered)->not->toContain('vecchio@example.com');
    });

    test('the notice to the current address pins the new email and the support instruction', function () {
        $rendered = (string) (new EmailChangeNoticeNotification('nuovo@example.com'))->toMail($this->user)->render();

        expect($rendered)->toContain('nuovo@example.com')
            ->toContain('contatta il supporto')
            ->not->toContain(url('/auth/email-change/'));
    });
});
