<?php

declare(strict_types=1);

/**
 * La suite gira in CI senza misura di copertura: il job pest esegue
 * `pest --ci` e l'action setup non attiva alcun driver (#83). Il guard
 * pinn il driver pcov e la soglia minima esplicita nel comando, così
 * una regressione di copertura spezza la CI invece di passare in silenzio.
 */
test('the pest ci job enforces a minimum coverage with the pcov driver', function () {
    $action = file_get_contents(base_path('.github/actions/test/action.yml'));
    expect($action)->toBeString();

    // Il driver va dichiarato sull'input `coverage` dello step Setup PHP
    // e la soglia deve essere un numero esplicito nel comando Pest.
    expect(preg_match('/coverage:\s*pcov/m', $action))->toBe(1)
        ->and(preg_match('/pest\s+--ci\s+--coverage\s+--min=\d+/m', $action))->toBe(1);
});
