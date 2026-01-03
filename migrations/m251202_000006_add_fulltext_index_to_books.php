<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251202_000006_add_fulltext_index_to_books extends Migration
{
    private const INDEX_NAME = 'ft_books_title_description';

    public function safeUp(): void
    {
        match ($this->db->driverName) {
            'mysql' => $this->execute('ALTER TABLE books ADD FULLTEXT INDEX ' . self::INDEX_NAME . ' (title, description)'),
            'pgsql' => $this->execute(
                'CREATE INDEX ' . self::INDEX_NAME . ' ON books USING gin(' .
                "to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, '')))"
            ),
            default => null,
        };
    }

    public function safeDown(): void
    {
        if ($this->db->driverName === 'sqlite') {
            return;
        }

        $this->dropIndex(self::INDEX_NAME, 'books');
    }
}
