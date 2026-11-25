<?php

declare(strict_types=1);

// Snapshot cifrati del database SQLite (#75). Il file cifrato vive nella
// directory backups/ dentro il volume persistente: il backup volume→R2 di
// Dokploy lo raccoglie. Nota onesta: l'archivio del volume contiene anche il
// database live IN CHIARO — la riservatezza di quella copia poggia sulle ACL
// del bucket e sulla cifratura at-rest di Cloudflare; gli snapshot cifrati
// aggiungono una copia consistente la cui riservatezza non dipende dal
// provider.
return [

    // Base64 di 32 byte (php -r "echo base64_encode(random_bytes(32));").
    // Da conservare ANCHE fuori host (password manager): senza chiave gli
    // snapshot non sono ripristinabili. Ruotarla rende indecifrabili quelli
    // esistenti (decriptare e ricifrare prima, o accettarne la perdita).
    'key' => env('BACKUP_SNAPSHOT_KEY'),

    // Quanti snapshot conservare nel volume (il più recente è sempre incluso).
    'keep' => (int) env('BACKUP_KEEP', 7),

    // Email avvisata a ogni fallimento del comando; vuota = nessun avviso.
    'alert_email' => env('BACKUP_ALERT_EMAIL'),

];
