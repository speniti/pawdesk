<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\EmailChangeCancellation;
use App\Filament\Pages\Auth\EmailChangeConfirmation;
use App\Filament\Pages\Auth\Profile;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmailChangeAppliedNotification;
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

        Notification::assertSentToTimes($this->user, EmailChangeNoticeNotification::class, 1);
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
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        get($links->confirmUrl)->assertOk()->assertSee("Conferma il cambio dell'email");

        assertGuest();

        expect($this->user->refresh()->email)->toBe('vecchio@example.com');

        Livewire::test(
            EmailChangeConfirmation::class,
            ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')]
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
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        $this->travel(16)->minutes();

        get($links->confirmUrl)
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');
    });
});

describe('link consumption', function () {
    test('applies the change for a guest visitor without authenticating them', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')])
            ->call('confirm')
            ->assertRedirect('/login');

        expect($this->user->refresh()->email)->toBe('nuovo@example.com');

        assertGuest();
    });

    test('marks the new email as verified when it is confirmed', function () {
        $this->user->forceFill(['email_verified_at' => null])->save();

        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')])
            ->call('confirm');

        expect($this->user->refresh()->email_verified_at)->not->toBeNull();
    });

    test('a consumed token cannot be used again', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');
        $token = (string) str($links->confirmUrl)->after('/auth/email-change/');

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
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        $component = Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')]);

        $this->travel(16)->minutes();

        $component->call('confirm')->assertRedirect();

        expect($this->user->refresh()->email)->toBe('vecchio@example.com')
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBeNull();
    });

    test('an email claimed by another user in the meantime is rejected cleanly', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'contesa@example.com');
        $token = (string) str($links->confirmUrl)->after('/auth/email-change/');

        User::factory()->create(['email' => 'contesa@example.com']);

        Livewire::test(EmailChangeConfirmation::class, ['token' => $token])
            ->call('confirm')
            ->assertRedirect()
            ->assertNotified("Non è stato possibile applicare il cambio dell'email.");

        expect($this->user->refresh()->email)->toBe('vecchio@example.com')
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBeNull();
    });

    test('a pending change for a deleted user fails generically', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        $component = Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')]);

        $this->user->delete();

        $component->call('confirm')
            ->assertRedirect()
            ->assertNotified("Non è stato possibile applicare il cambio dell'email.");

        expect(User::count())->toBe(0);
    });

    test('applying the change notifies the old address that it went through', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')])
            ->call('confirm')
            ->assertRedirect('/login');

        expect($this->user->refresh()->email)->toBe('nuovo@example.com');

        Notification::assertSentTo(
            Notification::route('mail', 'vecchio@example.com'),
            EmailChangeAppliedNotification::class,
            function (EmailChangeAppliedNotification $notification): bool {
                expect($notification->newEmail)->toBe('nuovo@example.com');

                return true;
            },
        );

        Notification::assertNotSentTo($this->user, EmailChangeAppliedNotification::class);
    });

    test('a rejected change notifies no one', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'contesa@example.com');

        User::factory()->create(['email' => 'contesa@example.com']);

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')])
            ->call('confirm')
            ->assertRedirect()
            ->assertNotified("Non è stato possibile applicare il cambio dell'email.");

        Notification::assertNotSentTo(
            Notification::route('mail', 'vecchio@example.com'),
            EmailChangeAppliedNotification::class,
        );

        expect($this->user->refresh()->email)->toBe('vecchio@example.com');
    });
});

describe('notification content', function () {
    test('the confirmation mail contains the link and never the current address', function () {
        $url = url('/auth/email-change/'.str()->random(64));

        $rendered = (string) (new EmailChangeConfirmationNotification($url))->toMail($this->user)->render();

        expect($rendered)->toContain($url)
            ->and($rendered)->not->toContain('vecchio@example.com');
    });

    test('the notice to the current address pins the new email, the cancel action and the support instruction', function () {
        $cancelUrl = url('/auth/email-change-cancel/'.str()->random(64));

        $rendered = (string) (new EmailChangeNoticeNotification('nuovo@example.com', $cancelUrl))->toMail($this->user)->render();

        expect($rendered)->toContain('nuovo@example.com')
            ->toContain('contatta il supporto')
            ->toContain($cancelUrl)
            // Literal segment with the trailing slash: url() strips it, and
            // the stripped form is a legitimate prefix of the cancel URL.
            ->not->toContain('/auth/email-change/');
    });

    test('the applied mail pins the new address and the support instruction', function () {
        $rendered = (string) (new EmailChangeAppliedNotification('nuovo@example.com'))->toMail($this->user)->render();

        expect($rendered)->toContain('nuovo@example.com')
            ->toContain('contatta il supporto');
    });
});

describe('cancel link', function () {
    test('the request issues a cancel link and the notice to the current address carries it', function () {
        requestEmailChangeViaProfile($this->user, $this->tenant, 'nuovo@example.com');

        Notification::assertSentTo(
            $this->user,
            EmailChangeNoticeNotification::class,
            function (EmailChangeNoticeNotification $notification): bool {
                expect($notification->cancelUrl)->toStartWith(url('/auth/email-change-cancel/'))
                    ->and((string) str($notification->cancelUrl)->after('/auth/email-change-cancel/'))->toHaveLength(64);

                return true;
            },
        );
    });

    test('the cancel link invalidates the pending change and its confirmation link', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');
        $confirmToken = (string) str($links->confirmUrl)->after('/auth/email-change/');

        // Mounted while the confirmation token is still alive: cancellation
        // happens in flight, then the visitor clicks confirm.
        $component = Livewire::test(EmailChangeConfirmation::class, ['token' => $confirmToken]);

        expect(app(PendingEmailService::class)->cancelByToken(
            (string) str($links->cancelUrl)->after('/auth/email-change-cancel/'),
        ))->toBeTrue()
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBeNull()
            ->and(app(PendingEmailService::class)->isValid($confirmToken))->toBeFalse();

        $component->call('confirm')
            ->assertRedirect()
            ->assertNotified("Non è stato possibile applicare il cambio dell'email.");

        expect($this->user->refresh()->email)->toBe('vecchio@example.com');
    });

    test('a cancel link is single use', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');
        $cancelToken = (string) str($links->cancelUrl)->after('/auth/email-change-cancel/');

        expect(app(PendingEmailService::class)->cancelByToken($cancelToken))->toBeTrue()
            ->and(app(PendingEmailService::class)->cancelByToken($cancelToken))->toBeFalse();
    });

    test('a cancel link cannot cancel a newer request', function () {
        // This pins the first defence line: issueChange() forgets the
        // superseded cancel key. The second line — cancelByToken()'s
        // cancelHash binding — covers the non-atomic replace race and is
        // not reachable from any serialized sequence, so it cannot be
        // mutation-verified black-box without writing cache state directly.
        $first = app(PendingEmailService::class)->issueChange($this->user, 'primo@example.com');
        $second = app(PendingEmailService::class)->issueChange($this->user, 'secondo@example.com');

        expect(app(PendingEmailService::class)->cancelByToken(
            (string) str($first->cancelUrl)->after('/auth/email-change-cancel/'),
        ))->toBeFalse()
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBe('secondo@example.com')
            ->and(app(PendingEmailService::class)->isValid((string) str($second->confirmUrl)->after('/auth/email-change/')))->toBeTrue();
    });

    test('confirming the change kills the cancel link', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');
        $cancelToken = (string) str($links->cancelUrl)->after('/auth/email-change-cancel/');

        Livewire::test(EmailChangeConfirmation::class, ['token' => (string) str($links->confirmUrl)->after('/auth/email-change/')])
            ->call('confirm');

        expect($this->user->refresh()->email)->toBe('nuovo@example.com')
            // The cancel PAGE must also stop offering the button: with only
            // cancelByToken false, a confirmed change would still render a
            // working-looking cancellation page to the owner.
            ->and(app(PendingEmailService::class)->isCancelValid($cancelToken))->toBeFalse()
            ->and(app(PendingEmailService::class)->cancelByToken($cancelToken))->toBeFalse();
    });

    test('an unknown cancel link fails generically', function () {
        expect(app(PendingEmailService::class)->cancelByToken(str()->random(64)))->toBeFalse();
    });
});

describe('cancellation page', function () {
    test('shows a cancellation page without consuming the token or authenticating', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        get($links->cancelUrl)->assertOk()->assertSee('Annulla il cambio');

        assertGuest();

        expect(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBe('nuovo@example.com');
    });

    test('redirects to login with an error when the cancel token is unknown', function () {
        get('/auth/email-change-cancel/'.str()->random(64))
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');
    });

    test('an expired cancel link fails generically', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        $this->travel(16)->minutes();

        get($links->cancelUrl)
            ->assertRedirect('/login')
            ->assertSessionHas('danger', 'Link non valido o scaduto. Richiedene uno nuovo.');

        expect(app(PendingEmailService::class)->cancelByToken(
            (string) str($links->cancelUrl)->after('/auth/email-change-cancel/'),
        ))->toBeFalse();
    });

    test('the cancel action cancels the pending change for a guest visitor', function () {
        $links = app(PendingEmailService::class)->issueChange($this->user, 'nuovo@example.com');

        Livewire::test(EmailChangeCancellation::class, ['token' => (string) str($links->cancelUrl)->after('/auth/email-change-cancel/')])
            ->call('cancel')
            ->assertRedirect('/login')
            ->assertNotified('Cambio email annullato');

        expect($this->user->refresh()->email)->toBe('vecchio@example.com')
            ->and(app(PendingEmailService::class)->pendingEmailFor($this->user))->toBeNull();
    });
});
