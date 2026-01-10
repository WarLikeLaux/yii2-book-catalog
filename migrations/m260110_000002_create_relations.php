<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260110_000002_create_relations extends Migration
{
    public function up(): void
    {
        $this->createTable('book_authors', [
            'book_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk_book_authors', 'book_authors', ['book_id', 'author_id']);

        $this->createIndex('idx_book_authors_author_id', 'book_authors', 'author_id');

        $this->addForeignKey(
            'fk_book_authors_book',
            'book_authors',
            'book_id',
            'books',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk_book_authors_author',
            'book_authors',
            'author_id',
            'authors',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function down(): void
    {
        $this->dropTable('book_authors');
    }
}
