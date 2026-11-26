<?php

declare(strict_types=1);

use App\Models\Tenant;

// Robustezza #143: senza unique() il pool faker ripete le company e il
// vincolo UNIQUE su tenants.slug esplode con probabilità bassa ma reale
// (visto in CI su #135, dove il bump di pail era estraneo). 300 creazioni
// rendono la collisione sulla vecchia factory quasi certa.
test('the tenant factory generates unique slugs across many creations', function () {
    $slugs = Tenant::factory()->count(300)->create()->pluck('slug');

    expect($slugs->unique()->count())->toBe(300);
});
