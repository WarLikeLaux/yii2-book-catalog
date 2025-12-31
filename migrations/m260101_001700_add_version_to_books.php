<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260101_001700_add_version_to_books extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('books', 'version', $this->integer()->notNull()->defaultValue(1));
    }

    public function safeDown(): void
    {
        $this->dropColumn('books', 'version');
    }
}
