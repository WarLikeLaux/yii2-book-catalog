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
            'author_id'
        );
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_book_authors_author_id', 'book_authors');
    }
}
