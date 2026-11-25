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
    // The policy must never promise marketing processing or a consent
    // revocation right, whatever the feature state behind the scenes:
    // both would be inaccurate transparency under art. 13 GDPR. The
    // marketing consent feature removal itself is tracked in #77/#91.
    $tenant = Tenant::factory()->create(['slug' => 'salone-privacy']);

    get('/salone-privacy/privacy-policy')
        ->assertOk()
        // The *Text variants match stripped, whitespace-collapsed text, so
        // a re-wrap of the same sentence cannot evade the pin.
        ->assertDontSeeText('comunicazioni promozionali')
        ->assertDontSeeText('marketing è facoltativo')
        ->assertDontSeeText('fino alla revoca del consenso')
        ->assertDontSeeText('revocare in qualsiasi momento il consenso')
        // Version anchor for accountability: every substantive revision
        // updates this date (and this pin together with it).
        ->assertSeeText('Ultimo aggiornamento')
        ->assertSeeText('25 novembre 2026')
        // Fallback §7 for tenants without privacy contact settings, which
        // this tenant exercises by using the bare factory defaults.
        ->assertSeeText('al contatto del titolare sopra indicato');
});

test('pagina informativa non richiede autenticazione', function () {
    $tenant = Tenant::factory()->create(['slug' => 'salone-aperto']);

    get('/salone-aperto/privacy-policy')->assertOk();
});

test('slug sconosciuto restituisce 404', function () {
    get('/salone-inesistente/privacy-policy')->assertNotFound();
});
