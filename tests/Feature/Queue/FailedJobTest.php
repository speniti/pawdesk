<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

describe('failed job handling', function () {
    test('failed_jobs table exists with correct schema', function () {
        expect(Schema::hasTable('failed_jobs'))->toBeTrue();

        expect(Schema::hasColumns('failed_jobs', [
            'id',
            'uuid',
            'connection',
            'queue',
            'payload',
            'exception',
            'failed_at',
        ]))->toBeTrue();
    });

    test('failed job can be recorded and queried', function () {
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-1234',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => true]),
            'exception' => 'RuntimeException: something went wrong',
            'failed_at' => now(),
        ]);

        $this->assertDatabaseHas('failed_jobs', [
            'uuid' => 'test-uuid-1234',
            'connection' => 'database',
            'queue' => 'default',
        ]);
    });
});
