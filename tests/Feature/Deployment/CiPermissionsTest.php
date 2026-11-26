<?php

declare(strict_types=1);

/**
 * ci.yml è il workflow che esegue install di dipendenze da PR (composer/npm)
 * ed è l'unico senza blocco permissions esplicito: i job ereditano il
 * default del repo, che potrebbe cambiare (#130). Il blocco esplicito è
 * defense in depth — nessun job di CI scrive, contents: read basta.
 */
test('the ci workflow declares least-privilege token permissions', function () {
    $ci = file_get_contents(base_path('.github/workflows/ci.yml'));
    expect($ci)->toBeString();

    // Blocco a livello workflow (colonna 0): contents: read esplicito.
    expect(preg_match('/^permissions:\s*$/m', $ci))->toBe(1)
        ->and(preg_match('/^permissions:\n  contents:\s*read\s*$/m', $ci))->toBe(1);
});
