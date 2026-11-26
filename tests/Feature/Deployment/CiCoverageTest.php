<?php

declare(strict_types=1);

/**
 * Il guard impedisce che il job pest perda il driver pcov o la soglia
 * esplicita: senza copertura la CI passerebbe anche con una regressione
 * (#83). Baseline misurata al primo run con pcov: 95.3% — la soglia a 92
 * lascia ~3 punti di margine; abbassarla richiede di toccare questo test.
 */
test('the pest ci job enforces a minimum coverage with the pcov driver', function () {
    $action = file_get_contents(base_path('.github/actions/test/action.yml'));
    expect($action)->toBeString();

    // Ancore a inizio riga: una riga di config commentata non deve far
    // passare il guard.
    expect(preg_match('/^\s*coverage:\s*pcov\s*$/m', $action))->toBe(1);

    // La soglia è un numero esplicito, non minore della baseline pinnata.
    preg_match(
        '/^\s*run:\s*php -d memory_limit=512M vendor\/bin\/pest\s+--ci\s+--coverage\s+--min=(\d+)/m',
        $action,
        $matches,
    );
    expect($matches)->toHaveCount(2)
        ->and((int) $matches[1])->toBeGreaterThanOrEqual(92);
});
