<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260207_000001_add_book_status extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('books', 'status', $this->string(20)->notNull()->defaultValue('draft'));

        $this->execute(
            "UPDATE books SET status = CASE WHEN is_published = 1 THEN 'published' ELSE 'draft' END",
        );

        $this->createIndex('idx_books_status', 'books', 'status');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_books_status', 'books');
        $this->dropColumn('books', 'status');
    }
}
