<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260108_120000_add_cover_url_partial_index extends Migration
{
    private const INDEX_NAME = 'idx_books_cover_url_not_null';
    private const MYSQL_MIN_VERSION = '8.0.13';

    public function safeUp(): void
    {
        match ($this->db->driverName) {
            'mysql' => $this->createMysqlIndex(),
            'pgsql' => $this->execute(
                'CREATE INDEX ' . self::INDEX_NAME . ' ON books (cover_url) WHERE cover_url IS NOT NULL',
            ),
            default => null,
        };
    }

    private function createMysqlIndex(): void
    {
        $version = $this->db->getServerVersion();

        if (version_compare($version, self::MYSQL_MIN_VERSION, '<')) {
            throw new RuntimeException(
                'Functional indexes require MySQL ' . self::MYSQL_MIN_VERSION . '+. Current: ' . $version,
            );
        }

        $this->execute(
            'CREATE INDEX ' . self::INDEX_NAME . ' ON books ((CASE WHEN cover_url IS NOT NULL THEN cover_url END))',
        );
    }

    public function safeDown(): void
    {
        $this->dropIndex(self::INDEX_NAME, 'books');
    }
}
