<?php

declare(strict_types=1);

use App\Models\Tenant;

use function Pest\Laravel\get;

test('pagina informativa pubblica mostra i dati del salone', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Salone Ace',
        'slug' => 'salone-ace',
        'settings' => [
            'privacy_business_name' => 'Ace S.r.l.',
            'privacy_business_address' => 'Via Roma 1, 00100 Roma (RM)',
        ],
    ]);

    get('/salone-ace/privacy-policy')
        ->assertOk()
        ->assertSee('Informativa sulla privacy')
        ->assertSee('Informativa sul trattamento dei dati personali — art. 13 Regolamento UE 2016/679 (GDPR)')
        ->assertSee('fi-width-3xl')
        ->assertSee('Ace S.r.l.')
        ->assertSee('Via Roma 1, 00100 Roma (RM)')
        ->assertSee('Titolare del trattamento');
});

test("l'informativa non promette più finalità di marketing basate sul consenso", function () {
    // After the marketing consent feature was removed (#77/#91), the policy
    // must not promise marketing processing or a consent-revocation right:
    // both would be inaccurate transparency under art. 13 GDPR.
    $tenant = Tenant::factory()->create(['slug' => 'salone-privacy']);

    get('/salone-privacy/privacy-policy')
        ->assertOk()
        ->assertDontSee('comunicazioni promozionali')
        ->assertDontSee('marketing è facoltativo')
        ->assertDontSee('fino alla revoca del consenso')
        ->assertDontSee('revocare in qualsiasi momento il consenso')
        // Version anchor for accountability: every substantive revision
        // updates this date (and this pin together with it).
        ->assertSee('Ultimo aggiornamento')
        ->assertSee('25 novembre 2026');
});

test('pagina informativa non richiede autenticazione', function () {
    $tenant = Tenant::factory()->create(['slug' => 'salone-aperto']);

    get('/salone-aperto/privacy-policy')->assertOk();
});

test('slug sconosciuto restituisce 404', function () {
    get('/salone-inesistente/privacy-policy')->assertNotFound();
});
