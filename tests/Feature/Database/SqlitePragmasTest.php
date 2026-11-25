<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/**
 * The three production processes (web, worker, scheduler) share the same
 * SQLite file on the volume: WAL, busy timeout and synchronous NORMAL are the
 * concurrency mitigation (#75). The pin is double-layered: the config values
 * must stay at contract values AND the connector must actually apply them on
 * a real file connection (:memory: always reports journal mode 'memory', so
 * the probe uses a temporary database).
 */
test('the production sqlite connection contract pins WAL, busy timeout and synchronous NORMAL', function () {
    $sqlite = config('database.connections.sqlite');

    expect($sqlite['journal_mode'])->toBe('WAL')
        ->and($sqlite['busy_timeout'])->toBe(10_000)
        ->and($sqlite['synchronous'])->toBe('NORMAL')
        ->and($sqlite['foreign_key_constraints'])->toBeTrue();
});

test('the sqlite connector actually applies the pinned pragmas on a file database', function () {
    $dbPath = sys_get_temp_dir().'/pawdesk-pragma-probe-'.uniqid().'.sqlite';
    touch($dbPath);
    $sqlite = config('database.connections.sqlite');

    config(['database.connections.pragma_probe' => [
        'driver' => 'sqlite',
        'database' => $dbPath,
        'prefix' => '',
        'foreign_key_constraints' => $sqlite['foreign_key_constraints'],
        'busy_timeout' => $sqlite['busy_timeout'],
        'journal_mode' => $sqlite['journal_mode'],
        'synchronous' => $sqlite['synchronous'],
    ]]);

    DB::purge('pragma_probe');

    try {
        $pdo = DB::connection('pragma_probe')->getPdo();

        expect($pdo->query('pragma journal_mode')->fetchColumn())->toBe('wal')
            ->and($pdo->query('pragma busy_timeout')->fetchColumn())->toBe(10_000)
            ->and($pdo->query('pragma synchronous')->fetchColumn())->toBe(1)
            ->and($pdo->query('pragma foreign_keys')->fetchColumn())->toBe(1);
    } finally {
        DB::disconnect('pragma_probe');
        @unlink($dbPath);
    }
});
