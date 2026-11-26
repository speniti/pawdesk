<?php

declare(strict_types=1);

/**
 * Un tag major di una action (es. actions/checkout@v4) è mutabile: se viene
 * ripuntato, codice arbitrario entra nella CI con le nostre credenziali
 * (#111). Ogni `uses:` esterno deve pinnare lo SHA completo del commit con
 * commento di versione leggibile; Dependabot (ecosistema github-actions)
 * aggiorna SHA e commento in sync.
 *
 * Qualsiasi riga contenente `uses:` deve risolvere a un pin valido o a
 * un'eccezione nota: i formati non statici (flow style, refs dinamiche
 * `${{ … }}`) sono violazioni, non omissioni silenziose.
 */
test('every external github action is pinned to a full commit sha with a version comment', function () {
    $files = array_merge(
        glob(base_path('.github/workflows/*.yml')) ?: [],
        glob(base_path('.github/workflows/*.yaml')) ?: [],
        glob(base_path('.github/actions/*/action.yml')) ?: [],
        glob(base_path('.github/actions/*/*/action.yml')) ?: [],
    );
    expect($files)->not->toBeEmpty();

    $violations = [];

    foreach ($files as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        expect($lines)->toBeArray();

        foreach ($lines as $line) {
            if (preg_match('/^\s*#/', $line) || ! str_contains($line, 'uses:')) {
                continue;
            }

            $relative = basename($file);
            $ref = mb_trim(mb_substr($line, (int) mb_strpos($line, 'uses:') + mb_strlen('uses:')));

            // Ref dinamico o flow style: non valutabili staticamente → violazione.
            if (str_contains($ref, '${{') || str_contains($ref, '{') || str_contains($ref, '}')) {
                $violations[] = $relative.': formato non statico: '.$ref;

                continue;
            }

            // Commento inline obbligatorio (`uses: <ref> # <versione>`).
            $hash = mb_strpos($ref, '#');
            $comment = $hash === false ? null : mb_trim(mb_substr($ref, $hash + 1));
            $action = mb_trim($hash === false ? $ref : mb_substr($ref, 0, $hash));

            // Action locali del repo: vivono qui, non sono tag mutabili.
            if (str_starts_with($action, './')) {
                continue;
            }

            // Container action: solo il digest è accettabile, il tag è mutabile.
            if (str_starts_with($action, 'docker://')) {
                if (! preg_match('/@sha256:[0-9a-f]{64}$/', $action)) {
                    $violations[] = $relative.': '.$action;
                }

                continue;
            }

            if ($comment === null || $comment === ''
                || ! preg_match('/^[^@\s]+@[0-9a-f]{40}$/', $action)) {
                $violations[] = $relative.': '.$action;
            }
        }
    }

    expect($violations)->toBe([]);
});
