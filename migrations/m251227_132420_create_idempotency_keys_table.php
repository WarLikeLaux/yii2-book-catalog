<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251227_132420_create_idempotency_keys_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('idempotency_keys', [
            'id' => $this->primaryKey(),
            'idempotency_key' => $this->string(36)->notNull()->unique(),
            'status_code' => $this->integer()->notNull(),
            'response_body' => $this->binary()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_idempotency_keys_expires_at', 'idempotency_keys', 'expires_at');
    }

    public function safeDown(): void
    {
        $this->dropTable('idempotency_keys');
    }
}
