<?php

declare(strict_types=1);

use yii\db\Migration;

class m260109_200000_add_queue_composite_index extends Migration
{
    public function safeUp()
    {
        $this->createIndex(
            'idx_queue_channel_reserved_at_priority',
            '{{%queue}}',
            ['channel', 'reserved_at', 'priority'],
            false,
        );
    }

    public function safeDown()
    {
        $this->dropIndex(
            'idx_queue_channel_reserved_at_priority',
            '{{%queue}}',
        );
    }
}
