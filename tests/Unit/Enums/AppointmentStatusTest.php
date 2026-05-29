<?php

declare(strict_types=1);

use App\Enums\AppointmentStatus;

describe('valid transitions', function () {
    $transitions = [
        'Requested -> Confirmed' => [AppointmentStatus::Requested, AppointmentStatus::Confirmed],
        'Requested -> Cancelled' => [AppointmentStatus::Requested, AppointmentStatus::Cancelled],
        'Confirmed -> InProgress' => [AppointmentStatus::Confirmed, AppointmentStatus::InProgress],
        'Confirmed -> Cancelled' => [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled],
        'Confirmed -> NoShow' => [AppointmentStatus::Confirmed, AppointmentStatus::NoShow],
        'InProgress -> Completed' => [AppointmentStatus::InProgress, AppointmentStatus::Completed],
        'InProgress -> Cancelled' => [AppointmentStatus::InProgress, AppointmentStatus::Cancelled],
        'InProgress -> NoShow' => [AppointmentStatus::InProgress, AppointmentStatus::NoShow],
    ];

    foreach ($transitions as $description => [$from, $to]) {
        test("$description is allowed", function () use ($from, $to) {
            expect($from->canTransitionTo($to))->toBeTrue();
        });
    }
});

describe('invalid transitions', function () {
    $invalidTransitions = [
        'Completed -> anything' => [AppointmentStatus::Completed, AppointmentStatus::Requested],
        'Cancelled -> anything' => [AppointmentStatus::Cancelled, AppointmentStatus::Requested],
        'NoShow -> anything' => [AppointmentStatus::NoShow, AppointmentStatus::Requested],
        'Requested -> Completed (skip)' => [AppointmentStatus::Requested, AppointmentStatus::Completed],
        'Requested -> InProgress (skip)' => [AppointmentStatus::Requested, AppointmentStatus::InProgress],
        'Confirmed -> Requested (reverse)' => [AppointmentStatus::Confirmed, AppointmentStatus::Requested],
        'InProgress -> Confirmed (reverse)' => [AppointmentStatus::InProgress, AppointmentStatus::Confirmed],
    ];

    foreach ($invalidTransitions as $description => [$from, $to]) {
        test("$description is rejected", function () use ($from, $to) {
            expect($from->canTransitionTo($to))->toBeFalse();
        });
    }
});

test('terminal statuses have no outgoing transitions', function () {
    $terminals = [AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow];

    foreach ($terminals as $terminal) {
        foreach (AppointmentStatus::cases() as $target) {
            expect($terminal->canTransitionTo($target))->toBeFalse();
        }
    }
});

test('nextStatuses returns correct set per status', function () {
    expect(AppointmentStatus::Requested->nextStatuses())
        ->toBe([AppointmentStatus::Confirmed, AppointmentStatus::Cancelled]);

    expect(AppointmentStatus::Confirmed->nextStatuses())
        ->toBe([AppointmentStatus::InProgress, AppointmentStatus::Cancelled, AppointmentStatus::NoShow]);

    expect(AppointmentStatus::InProgress->nextStatuses())
        ->toBe([AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow]);

    expect(AppointmentStatus::Completed->nextStatuses())->toBe([]);
    expect(AppointmentStatus::Cancelled->nextStatuses())->toBe([]);
    expect(AppointmentStatus::NoShow->nextStatuses())->toBe([]);
});
