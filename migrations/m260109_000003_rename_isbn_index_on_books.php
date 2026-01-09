<?php

declare(strict_types=1);

use yii\db\IndexConstraint;
use yii\db\Migration;

final class m260109_000003_rename_isbn_index_on_books extends Migration
{
    private const INDEX_NAME = 'idx_books_isbn';

    public function safeUp(): void
    {
        $indexes = $this->db->schema->getTableIndexes('books');
        $existingIndexNames = [];
        $hasTarget = false;

        foreach ($indexes as $index) {
            if ($index->isPrimary || !$index->isUnique) {
                continue;
            }

            if (!$this->isIsbnIndex($index)) {
                continue;
            }

            if ($index->name === self::INDEX_NAME) {
                $hasTarget = true;
                continue;
            }

            if ($index->name === null) {
                continue;
            }

            $existingIndexNames[] = $index->name;
        }

        foreach ($existingIndexNames as $name) {
            $this->dropUniqueIndex($name);
        }

        if ($hasTarget) {
            return;
        }

        $this->createIndex(self::INDEX_NAME, 'books', 'isbn', true);
    }

    public function safeDown(): void
    {
        $this->dropUniqueIndex(self::INDEX_NAME);
        $this->createIndex('isbn', 'books', 'isbn', true);
    }

    private function isIsbnIndex(IndexConstraint $index): bool
    {
        $columns = is_array($index->columnNames) ? $index->columnNames : [$index->columnNames];
        $columns = array_values($columns);

        return $columns === ['isbn'];
    }

    private function dropUniqueIndex(string $name): void
    {
        if ($this->db->driverName === 'pgsql') {
            $this->execute('ALTER TABLE books DROP CONSTRAINT IF EXISTS ' . $name);
            return;
        }

        $this->dropIndex($name, 'books');
    }
}
