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

test('pagina informativa non richiede autenticazione', function () {
    $tenant = Tenant::factory()->create(['slug' => 'salone-aperto']);

    get('/salone-aperto/privacy-policy')->assertOk();
});

test('slug sconosciuto restituisce 404', function () {
    get('/salone-inesistente/privacy-policy')->assertNotFound();
});
