<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251231_011600_make_author_fio_unique extends Migration
{
    public function safeUp(): void
    {
        // First delete duplicates if any exist to ensure unique index can be created
        // In a real prod env this might need manual intervention, but for local/test we can clean up
        $sql = 'DELETE t1 FROM authors t1
                INNER JOIN authors t2 
                WHERE t1.id < t2.id AND t1.fio = t2.fio';
        $this->execute($sql);

        // Drop existing non-unique index
        $this->dropIndex('idx_authors_fio', 'authors');

        // Create unique index
        $this->createIndex('idx_authors_fio', 'authors', 'fio', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_authors_fio', 'authors');
        $this->createIndex('idx_authors_fio', 'authors', 'fio');
    }
}
