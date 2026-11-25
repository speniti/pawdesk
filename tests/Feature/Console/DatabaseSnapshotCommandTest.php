<?php

declare(strict_types=1);

use App\Notifications\BackupFailedNotification;
use Illuminate\Encryption\Encrypter;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function snapshotKey(): string
{
    return base64_encode(random_bytes(32));
}

function decryptSnapshot(string $relativePath, string $base64Key): string
{
    $encrypter = new Encrypter(base64_decode($base64Key, true), 'AES-256-GCM');

    return $encrypter->decryptString(Storage::disk('local')->get($relativePath));
}

beforeEach(function () {
    Storage::fake('local');
    config()->set('backup.key', snapshotKey());
    config()->set('backup.alert_email', 'admin@example.com');

    // Il comando fotografia lo stato COMMITTATO del file sqlite aprendo un
    // PDO dedicato: VACUUM non può girare sul PDO dell'app (il refresh di
    // Laravel lo tiene in transazione) e :memory: non è un file che il PDO
    // dedicato possa aprire. I test redirigono la connessione sqlite a un
    // file temporaneo committato, con uno schema minimo e un marker
    // univoco per provare che lo snapshot contiene QUESTO database.
    $this->snapshotDbPath = sys_get_temp_dir().'/pawdesk-snapshot-test-'.uniqid().'.sqlite';
    touch($this->snapshotDbPath);
    config()->set('database.connections.sqlite.database', $this->snapshotDbPath);
    DB::purge('sqlite');

    DB::connection('sqlite')->statement(
        'create table migrations (id integer primary key autoincrement, migration text not null, batch integer not null)',
    );
    DB::connection('sqlite')->table('migrations')->insert(['migration' => 'probe', 'batch' => 1]);

    $this->marker = uniqid('snapshot-marker-', true);
    DB::connection('sqlite')->statement('create table snapshot_marker (marker text not null)');
    DB::connection('sqlite')->table('snapshot_marker')->insert(['marker' => $this->marker]);
});

afterEach(function () {
    DB::disconnect('sqlite');
    @unlink($this->snapshotDbPath);
    @unlink($this->snapshotDbPath.'-wal');
    @unlink($this->snapshotDbPath.'-shm');
});

test('fails closed and alerts when the snapshot key is not configured', function () {
    Notification::fake();
    config()->set('backup.key', null);

    $this->artisan('db:snapshot')
        ->assertFailed()
        ->expectsOutputToContain('BACKUP_SNAPSHOT_KEY');

    expect(Storage::disk('local')->files('backups'))->toBeEmpty();

    Notification::assertSentTo(
        new AnonymousNotifiable(),
        BackupFailedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routeNotificationFor('mail') === 'admin@example.com',
    );
});

test('fails closed on a malformed or wrong-length key', function (string $badKey) {
    Notification::fake();
    config()->set('backup.key', $badKey);

    $this->artisan('db:snapshot')
        ->assertFailed()
        ->expectsOutputToContain('BACKUP_SNAPSHOT_KEY');

    expect(Storage::disk('local')->files('backups'))->toBeEmpty();

    Notification::assertSentTo(new AnonymousNotifiable(), BackupFailedNotification::class);
})->with([
    'non base64' => ['not-base64!!!'],
    'chiave troppo corta' => [fn () => base64_encode(random_bytes(16))],
]);

test('refuses to run when the default connection is not sqlite', function () {
    Notification::fake();
    config()->set('database.default', 'pgsql');

    $this->artisan('db:snapshot')
        ->assertFailed()
        ->expectsOutputToContain('only supports the sqlite driver');

    expect(Storage::disk('local')->files('backups'))->toBeEmpty();
});

test('creates an encrypted snapshot that decrypts to an intact database', function () {
    $key = config('backup.key');

    $this->artisan('db:snapshot')->assertSuccessful();

    $files = Storage::disk('local')->files('backups');
    expect(count($files))->toBe(1)
        ->and(str_ends_with((string) $files[0], '.sqlite.enc'))->toBeTrue()
        // Nessun file in chiaro nel volume: solo ciphertext.
        ->and(preg_grep('/\.sqlite$/', $files))->toBeEmpty();

    $plaintext = decryptSnapshot((string) $files[0], (string) $key);
    $tmp = sys_get_temp_dir().'/snapshot-verify-'.uniqid();
    file_put_contents($tmp, $plaintext);

    try {
        $pdo = new PDO('sqlite:'.$tmp);
        expect($pdo->query('pragma integrity_check')->fetchColumn())->toBe('ok')
            ->and((int) $pdo->query('select count(*) from migrations')->fetchColumn())->toBeGreaterThan(0)
            // Lo snapshot contiene davvero IL database sotto test, non un
            // qualsiasi sqlite con lo schema minimo.
            ->and((string) $pdo->query('select marker from snapshot_marker limit 1')->fetchColumn())->toBe($this->marker);
    } finally {
        @unlink($tmp);
    }
});

test('the snapshot captures committed data only, even with an open write transaction', function () {
    $key = config('backup.key');

    // Transazione aperta (come un writer in produzione): l'insert in-flight
    // NON deve finire nello snapshot, quello committato sì (WAL, promise #75).
    DB::connection('sqlite')->beginTransaction();
    $inFlight = uniqid('in-flight-', true);
    DB::connection('sqlite')->table('snapshot_marker')->insert(['marker' => $inFlight]);

    $this->artisan('db:snapshot')->assertSuccessful();

    DB::connection('sqlite')->rollBack();

    $plaintext = decryptSnapshot((string) Storage::disk('local')->files('backups')[0], (string) $key);
    $tmp = sys_get_temp_dir().'/snapshot-commit-'.uniqid();
    file_put_contents($tmp, $plaintext);

    try {
        $pdo = new PDO('sqlite:'.$tmp);
        $markers = $pdo->query('select marker from snapshot_marker')->fetchAll(PDO::FETCH_COLUMN);
        expect($markers)->toContain($this->marker)
            ->and($markers)->not->toContain($inFlight);
    } finally {
        @unlink($tmp);
    }
});

test('prunes snapshots beyond the configured keep', function (int $keep, int $expectedFiles, bool $secondKept) {
    config()->set('backup.keep', $keep);
    $disk = Storage::disk('local');
    $disk->put('backups/db-20261101-000000000000.sqlite.enc', 'old');
    $disk->put('backups/db-20261102-000000000000.sqlite.enc', 'keep-me');

    $this->artisan('db:snapshot')->assertSuccessful();

    $files = $disk->files('backups');
    expect(count($files))->toBe($expectedFiles)
        ->and($disk->exists('backups/db-20261101-000000000000.sqlite.enc'))->toBeFalse()
        ->and($disk->exists('backups/db-20261102-000000000000.sqlite.enc'))->toBe($secondKept);
})->with([
    'keep 2' => ['keep' => 2, 'expectedFiles' => 2, 'secondKept' => true],
    'keep 1' => ['keep' => 1, 'expectedFiles' => 1, 'secondKept' => false],
    'keep 0 viene saturato a 1' => ['keep' => 0, 'expectedFiles' => 1, 'secondKept' => false],
]);

test('verify-only validates the latest snapshot without creating one', function () {
    $this->artisan('db:snapshot')->assertSuccessful();
    $countBefore = count(Storage::disk('local')->files('backups'));

    $this->artisan('db:snapshot', ['--verify-only' => true])->assertSuccessful();

    expect(count(Storage::disk('local')->files('backups')))->toBe($countBefore);
});

test('verify-only fails when no snapshot exists at all', function () {
    Notification::fake();

    $this->artisan('db:snapshot', ['--verify-only' => true])
        ->assertFailed()
        ->expectsOutputToContain('No snapshot');

    Notification::assertSentTo(new AnonymousNotifiable(), BackupFailedNotification::class);
});

test('a corrupt latest snapshot fails the verification and alerts the admin', function () {
    Notification::fake();
    Storage::disk('local')->put('backups/db-20991231-235959000000.sqlite.enc', 'not-a-real-snapshot');

    $this->artisan('db:snapshot', ['--verify-only' => true])->assertFailed();

    Notification::assertSentTo(
        new AnonymousNotifiable(),
        BackupFailedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routeNotificationFor('mail') === 'admin@example.com',
    );
});

test('an unset alert email still fails the command, just without a notification', function () {
    Notification::fake();
    config()->set('backup.key', null);
    config()->set('backup.alert_email', null);

    $this->artisan('db:snapshot')->assertFailed();

    Notification::assertNothingSent();
    expect(Storage::disk('local')->files('backups'))->toBeEmpty();
});
