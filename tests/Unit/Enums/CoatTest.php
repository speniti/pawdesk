<?php

declare(strict_types=1);

use App\Enums\Coat;

test('includes the wire coat with ruvido label', function () {
    $wire = Coat::tryFrom('wire');

    expect($wire)->not->toBeNull()
        ->and($wire->value)->toBe('wire')
        ->and($wire->getLabel())->toBe('Ruvido');
});

test('every coat case exposes a non-empty value, label and color', function () {
    foreach (Coat::cases() as $case) {
        expect($case->value)->not->toBeEmpty()
            ->and($case->getLabel())->not->toBeEmpty()
            ->and($case->getColor())->not->toBeEmpty();
    }
});
