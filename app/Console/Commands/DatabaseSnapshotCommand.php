<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Notifications\BackupFailedNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Consistent, encrypted SQLite snapshots inside the persistent volume (#75).
 *
 * The whole volume (live plaintext database included) is already shipped to
 * R2 by Dokploy's volume backup, but a raw archive of the live files (with
 * -wal/-shm mid-write) can be torn. This command produces an atomic VACUUM
 * INTO copy of the committed state, encrypts it client-side (AES-256-GCM —
 * the only DB artifact in the bucket whose confidentiality does NOT depend
 * on the bucket ACLs), prunes old snapshots and verifies the latest one
 * end-to-end by decrypting, opening it and running integrity checks —
 * art. 32(1)(d) GDPR restore testing.
 */
#[Signature('db:snapshot {--verify-only : Verify the latest snapshot without creating a new one}')]
#[Description('Create an encrypted SQLite snapshot in the persistent volume and verify it restores')]
class DatabaseSnapshotCommand extends Command
{
    public function handle(): int
    {
        try {
            // The snapshot is SQLite-specific (VACUUM INTO): refuse to run
            // when the default connection is another driver, so a future
            // pgsql switch cannot silently back up a stale sqlite file (#75).
            $default = (string) config('database.default');
            if ($default !== 'sqlite') {
                throw new RuntimeException("db:snapshot only supports the sqlite driver; the default connection is '{$default}'.");
            }

            $encrypter = $this->resolveEncrypter();

            if (! $this->option('verify-only')) {
                $latest = $this->createSnapshot($encrypter);
                $this->pruneOldSnapshots();

                $this->info("Encrypted snapshot created: {$latest}");
            }

            $this->verifyLatestSnapshot($encrypter);
            $this->info('Restore verification passed: integrity_check ok, migrations table populated.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Snapshot failed: '.$exception->getMessage());
            $this->alertAdmin($exception);

            return self::FAILURE;
        }
    }

    private function alertAdmin(Throwable $exception): void
    {
        $email = config('backup.alert_email');

        if (! is_string($email) || $email === '') {
            return;
        }

        // A failing mail transport must not replace the original failure as
        // the command's exit story.
        try {
            Notification::route('mail', $email)->notify(new BackupFailedNotification($exception->getMessage()));
        } catch (Throwable $alertFailure) {
            $this->error('Alert email failed: '.$alertFailure->getMessage());
        }
    }

    /**
     * The snapshot lives inside the persistent volume (the local disk root is
     * storage/app/private), so the existing Dokploy volume backup picks it up
     * on its way to R2 — deliberately not configurable until a second target
     * actually exists (YAGNI).
     *
     * @return array{0: \Illuminate\Contracts\Filesystem\Filesystem, 1: string}
     */
    private function backupTarget(): array
    {
        return [Storage::disk('local'), 'backups'];
    }

    private function createSnapshot(Encrypter $encrypter): string
    {
        [$disk, $dir] = $this->backupTarget();
        $name = 'db-'.now()->format('Ymd-Hisv').'.sqlite.enc';

        // Plain bytes never touch the volume: the temp copy lives in the
        // container-local tmp dir and is deleted right after encryption.
        $plaintextPath = (string) tempnam(sys_get_temp_dir(), 'pawdesk-snapshot-');
        // VACUUM INTO requires the target NOT to exist (a zero-byte file only
        // works on modern SQLite): unlink so any SQLite version can create it.
        @unlink($plaintextPath);

        try {
            $target = str_replace("'", "''", $plaintextPath);
            // A dedicated connection to the sqlite file: VACUUM cannot run on
            // a PDO with an open transaction (the app's connection can hold
            // one), and a backup must capture the COMMITTED state on disk
            // anyway. Same busy_timeout contract as the app connection.
            $pdo = new PDO('sqlite:'.(string) config('database.connections.sqlite.database'), null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec('pragma busy_timeout = '.(int) config('database.connections.sqlite.busy_timeout', 10_000));
            $pdo->exec("VACUUM INTO '{$target}'");
            $pdo = null;

            $contents = file_get_contents($plaintextPath);
            if ($contents === false) {
                throw new RuntimeException('Failed reading the VACUUM INTO temp copy.');
            }

            // The local disk is throw=false (framework default): a failed
            // write would return false silently and verification would then
            // pass on the PREVIOUS snapshot — staleness without any alert.
            $written = $disk->put($dir.'/'.$name, $encrypter->encryptString($contents));
            if ($written === false) {
                throw new RuntimeException("Failed writing the encrypted snapshot to {$dir}/{$name} (disk full or permissions?).");
            }
        } finally {
            @unlink($plaintextPath);
        }

        return $dir.'/'.$name;
    }

    private function pruneOldSnapshots(): void
    {
        [$disk, $dir] = $this->backupTarget();
        $keep = max(1, (int) config('backup.keep', 7));

        collect($disk->files($dir))
            ->filter(fn (string $path): bool => (bool) preg_match('#/db-.*\.sqlite\.enc$#', $path))
            ->sortDesc()
            ->values()
            ->slice($keep)
            ->each(fn (string $path) => $disk->delete($path));
    }

    private function resolveEncrypter(): Encrypter
    {
        $base64 = config('backup.key');

        if (! is_string($base64) || $base64 === '') {
            throw new RuntimeException('BACKUP_SNAPSHOT_KEY is not configured: generate one with php -r "echo base64_encode(random_bytes(32));" and set it in the Dokploy Environment tab.');
        }

        $key = base64_decode($base64, true);

        // 8bit encoding: the key is binary, mb_strlen without an explicit
        // encoding counts UTF-8 characters and would reject most valid keys
        // (same convention as Encrypter::supported()).
        if ($key === false || mb_strlen($key, '8bit') !== 32) {
            throw new RuntimeException('BACKUP_SNAPSHOT_KEY is invalid: it must be base64 of exactly 32 bytes.');
        }

        return new Encrypter($key, 'AES-256-GCM');
    }

    private function verifyLatestSnapshot(Encrypter $encrypter): void
    {
        [$disk, $dir] = $this->backupTarget();

        $latest = collect($disk->files($dir))
            ->filter(fn (string $path): bool => (bool) preg_match('#/db-.*\.sqlite\.enc$#', $path))
            ->sortDesc()
            ->first();

        if ($latest === null) {
            throw new RuntimeException('No snapshot found to verify.');
        }

        $plaintext = $encrypter->decryptString((string) $disk->get($latest));
        $path = (string) tempnam(sys_get_temp_dir(), 'pawdesk-verify-');

        try {
            file_put_contents($path, $plaintext);

            $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            $integrity = $pdo->query('pragma integrity_check')->fetchColumn();
            if ($integrity !== 'ok') {
                throw new RuntimeException("integrity_check returned {$integrity}.");
            }

            $hasMigrations = (int) $pdo->query(
                "select count(*) from sqlite_master where type = 'table' and name = 'migrations'",
            )->fetchColumn();
            if ($hasMigrations === 0) {
                throw new RuntimeException('The snapshot does not contain the migrations table.');
            }

            if ((int) $pdo->query('select count(*) from migrations')->fetchColumn() === 0) {
                throw new RuntimeException('The migrations table in the snapshot is empty.');
            }
        } finally {
            @unlink($path);
        }
    }
}
