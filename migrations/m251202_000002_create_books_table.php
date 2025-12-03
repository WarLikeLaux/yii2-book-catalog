<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251202_000002_create_books_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('books', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull()->comment('Название книги'),
            'year' => $this->integer()->notNull()->comment('Год выпуска'),
            'description' => $this->text()->comment('Описание'),
            'isbn' => $this->string(20)->notNull()->unique()->comment('ISBN'),
            'cover_url' => $this->string(500)->comment('URL обложки'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_books_year', 'books', 'year');
        $this->createIndex('idx_books_title', 'books', 'title');
    }

    public function safeDown(): void
    {
        $this->dropTable('books');
    }
}
