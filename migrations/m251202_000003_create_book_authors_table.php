<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251202_000003_create_book_authors_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('book_authors', [
            'book_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk_book_authors', 'book_authors', ['book_id', 'author_id']);
        $this->addForeignKey(
            'fk_book_authors_book',
            'book_authors',
            'book_id',
            'books',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_book_authors_author',
            'book_authors',
            'author_id',
            'authors',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('book_authors');
    }
}
