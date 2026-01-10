<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260109_210000_drop_queue_single_indexes extends Migration
{
    public function safeUp(): void
    {
        $this->dropIndex('channel', '{{%queue}}');
        $this->dropIndex('priority', '{{%queue}}');
    }

    public function safeDown(): void
    {
        $this->createIndex('channel', '{{%queue}}', 'channel');
        $this->createIndex('priority', '{{%queue}}', 'priority');
    }
}
