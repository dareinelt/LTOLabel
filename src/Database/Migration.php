<?php

declare(strict_types=1);

namespace App\Database;

/**
 * Creates/updates the database schema. Idempotent; safe to run on every boot.
 */
final class Migration
{
    public function migrate(Database $db): void
    {
        $isMySql = $db->driver() === 'mysql';
        $pk = $isMySql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

        $db->execute(sprintf(
            'CREATE TABLE IF NOT EXISTS labels (
                id %s,
                label_code VARCHAR(64) NOT NULL UNIQUE,
                media_type VARCHAR(16) NOT NULL,
                media_generation VARCHAR(8) NOT NULL,
                barcode_type VARCHAR(16) NOT NULL DEFAULT \'CODE39\',
                notes TEXT,
                location VARCHAR(255),
                status VARCHAR(32),
                batch_id INTEGER,
                created_at VARCHAR(32) NOT NULL,
                printed_at VARCHAR(32),
                print_count INTEGER NOT NULL DEFAULT 0
            )',
            $pk
        ));

        $db->execute('CREATE INDEX IF NOT EXISTS idx_labels_media_type ON labels(media_type)');
        $db->execute('CREATE INDEX IF NOT EXISTS idx_labels_batch ON labels(batch_id)');
        $db->execute('CREATE INDEX IF NOT EXISTS idx_labels_created ON labels(created_at)');
    }
}
