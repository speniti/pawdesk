<?php

declare(strict_types=1);

/**
 * Un tag floating si risolve a contenuti diversi nel tempo: un rebuild può
 * introdurre dipendenze di sistema diverse o, nel caso peggiore, portare in
 * produzione un tag compromesso senza alcuna verifica (#74). Ogni immagine
 * base del Dockerfile deve quindi essere pinnata al digest dell'index
 * multi-arch; gli aggiornamenti li propone Dependabot (ecosistema docker).
 */
test('every dockerfile base image is pinned by digest', function () {
    $lines = file(base_path('Dockerfile'), FILE_IGNORE_NEW_LINES);
    expect($lines)->toBeArray();

    $stageAliases = [];
    $floating = [];

    foreach ($lines as $line) {
        // Il flag opzionale (--platform=…) non fa parte dell'immagine base.
        if (! preg_match('/^\s*FROM\s+(?:--[^\s]+\s+)?(\S+)(?:\s+AS\s+(\S+))?/i', $line, $matches)) {
            continue;
        }

        $base = $matches[1];
        $alias = $matches[2] ?? null;

        // L'alias va registrato PRIMA del check di riusa, così anche uno stage
        // intermedio (es. `FROM dev AS php-deps`) è riusabile come base dopo.
        if ($alias !== null) {
            $stageAliases[] = $alias;
        }

        // `FROM dev AS production` riusa uno stage interno: non è un'immagine base.
        if (in_array($base, $stageAliases, true)) {
            continue;
        }

        if (! preg_match('/@sha256:[0-9a-f]{64}$/', $base)) {
            $floating[] = $base;
        }
    }

    expect($floating)->toBe([]);
});
