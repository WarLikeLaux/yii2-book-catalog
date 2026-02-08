<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260207_100000_drop_is_published extends Migration
{
    public function safeUp(): void
    {
        $this->dropColumn('books', 'is_published');
    }

    public function safeDown(): void
    {
        $this->addColumn('books', 'is_published', $this->boolean()->notNull()->defaultValue(0));

        $this->execute(
            "UPDATE books SET is_published = CASE WHEN status = 'published' THEN 1 ELSE 0 END",
        );
    }
}
