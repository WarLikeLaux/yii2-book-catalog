<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260107_120000_create_async_idempotency_log_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('async_idempotency_log', [
            'id' => $this->primaryKey(),
            'idempotency_key' => $this->string(128)->notNull()->unique(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_async_idempotency_log_created_at', 'async_idempotency_log', 'created_at');
    }

    public function safeDown(): void
    {
        $this->dropTable('async_idempotency_log');
    }
}
