<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251231_112400_add_is_published_to_books extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('books', 'is_published', (string)$this->boolean()->notNull()->defaultValue(false)->after('cover_url'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('books', 'is_published');
    }
}
