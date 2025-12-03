<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251202_000006_add_fulltext_index_to_books extends Migration
{
    public function safeUp(): void
    {
        $this->execute('ALTER TABLE `books` ADD FULLTEXT INDEX `ft_books_title_description` (`title`, `description`)');
    }

    public function safeDown(): void
    {
        $this->dropIndex('ft_books_title_description', 'books');
    }
}
