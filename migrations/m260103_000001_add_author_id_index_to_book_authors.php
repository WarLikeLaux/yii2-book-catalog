<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260103_000001_add_author_id_index_to_book_authors extends Migration
{
    public function safeUp(): void
    {
        $this->createIndex(
            'idx_book_authors_author_id',
            'book_authors',
            'author_id',
        );
    }

    public function safeDown(): void
    {
        $schema = $this->db->schema->getTableSchema('book_authors', true);

        if ($schema === null) {
            return;
        }

        foreach ($this->db->schema->getTableIndexes('book_authors') as $index) {
            if ($index->name !== 'idx_book_authors_author_id') {
                continue;
            }

            if (
                $this->db->driverName === 'mysql'
                && array_key_exists('fk_book_authors_author', $schema->foreignKeys)
            ) {
                $this->dropForeignKey('fk_book_authors_author', 'book_authors');
                $this->dropIndex('idx_book_authors_author_id', 'book_authors');
                $this->addForeignKey(
                    'fk_book_authors_author',
                    'book_authors',
                    'author_id',
                    'authors',
                    'id',
                    'CASCADE',
                    'CASCADE',
                );
                return;
            }

            $this->dropIndex('idx_book_authors_author_id', 'book_authors');
            return;
        }
    }
}
