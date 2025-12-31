<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251231_210000_add_fulltext_index_to_authors extends Migration
{
    public function safeUp(): void
    {
        $this->execute('ALTER TABLE `authors` ADD FULLTEXT INDEX `ft_authors_fio` (`fio`)');
    }

    public function safeDown(): void
    {
        $this->dropIndex('ft_authors_fio', 'authors');
    }
}
