<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260108_120000_add_cover_url_partial_index extends Migration
{
    public function safeUp(): void
    {
        $driver = $this->db->getDriverName();

        if ($driver === 'pgsql') {
            $this->execute('CREATE INDEX idx_books_cover_url_not_null ON books (cover_url) WHERE cover_url IS NOT NULL');
        } else {
            $this->execute('CREATE INDEX idx_books_cover_url_not_null ON books ((CASE WHEN cover_url IS NOT NULL THEN cover_url END))');
        }
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_books_cover_url_not_null', 'books');
    }
}
