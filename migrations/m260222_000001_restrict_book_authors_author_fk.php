<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260222_000001_restrict_book_authors_author_fk extends Migration
{
    public function safeUp(): void
    {
        $this->dropForeignKey('fk_book_authors_author', 'book_authors');

        $this->addForeignKey(
            'fk_book_authors_author',
            'book_authors',
            'author_id',
            'authors',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_book_authors_author', 'book_authors');

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
}
