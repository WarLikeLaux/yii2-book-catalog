<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260104_000001_add_unique_index_to_authors_fio extends Migration
{
    private const INDEX_NAME = 'ux_authors_fio';

    public function safeUp(): void
    {
        $this->dropIndex('idx_authors_fio', 'authors');
        $this->createIndex(self::INDEX_NAME, 'authors', 'fio', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex(self::INDEX_NAME, 'authors');
        $this->createIndex('idx_authors_fio', 'authors', 'fio');
    }
}
