<?php

declare(strict_types=1);

/**
 * phpstan.neon.dist analizza solo app/: i test contengono logica non banale
 * e sfuggono all'analisi statica (#83). Il guard pinn sia il path tests/
 * sia il binding ufficiale di Pest per PHPStan — senza il plugin, ogni
 * closure verrebbe tipata come PendingCalls\TestCall e il rumore renderebbe
 * l'analisi inutilizzabile.
 */
test('phpstan analyses the tests directory with the pest type binding plugin', function () {
    $neon = file_get_contents(base_path('phpstan.neon.dist'));
    expect($neon)->toBeString();

    expect(preg_match('/^\s+- tests\/$/m', $neon))->toBe(1)
        ->and(mb_substr_count($neon, 'pestphp/pest-plugin-phpstan/extension.neon'))->toBe(1);
});
