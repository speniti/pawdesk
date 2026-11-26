<?php

declare(strict_types=1);

/**
 * La CI non deve limitarsi a validare textualmente i pin: i bump settimanali
 * di digest che Dependabot propone sull'immagine base possono rompere la
 * build reale (es. intl incompatibile con la nuova base) ed emergerebbero
 * solo al deploy (#112). Il workflow deve contenere un job che costruisce
 * davvero il target production.
 */
test('the ci workflow builds the production docker image', function () {
    $ci = file_get_contents(base_path('.github/workflows/ci.yml'));
    expect($ci)->toBeString();

    // Job `docker` a livello root (indentazione 2 spazi) con build del
    // target production: un commento o un riferimento any-descriptor non
    // contano — la build deve essere eseguita.
    expect(preg_match('/^  docker:\s*$/m', $ci))->toBe(1)
        ->and(preg_match('/buildx build .*--target production/m', $ci))->toBe(1);
});
