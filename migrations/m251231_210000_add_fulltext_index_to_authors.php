<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251231_210000_add_fulltext_index_to_authors extends Migration
{
    private const INDEX_NAME = 'ft_authors_fio';

    public function safeUp(): void
    {
        match ($this->db->driverName) {
            'mysql' => $this->createIndex(self::INDEX_NAME, 'authors', 'fio', 'FULLTEXT'),
            'pgsql' => $this->execute(
                'CREATE INDEX ' . self::INDEX_NAME . " ON authors USING gin(to_tsvector('english', coalesce(fio, '')))"
            ),
            default => null,
        };
    }

    public function safeDown(): void
    {
        if ($this->db->driverName === 'sqlite') {
            return;
        }

        $this->dropIndex(self::INDEX_NAME, 'authors');
    }
}
