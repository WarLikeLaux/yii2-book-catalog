<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260110_000004_create_idempotency extends Migration
{
    public function up(): void
    {
        $this->createIdempotencyKeysTable();
        $this->createAsyncIdempotencyLogTable();
    }

    public function down(): void
    {
        $this->dropTable('async_idempotency_log');
        $this->dropTable('idempotency_keys');
    }

    private function createIdempotencyKeysTable(): void
    {
        $this->createTable('idempotency_keys', [
            'id' => $this->primaryKey(),
            'idempotency_key' => $this->string(128)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('finished'),
            'status_code' => $this->integer()->null(),
            'response_body' => $this->binary()->null(),
            'created_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_idempotency_keys_key', 'idempotency_keys', 'idempotency_key', true);
        $this->createIndex('idx_idempotency_keys_expires_at', 'idempotency_keys', 'expires_at');
    }

    private function createAsyncIdempotencyLogTable(): void
    {
        $this->createTable('async_idempotency_log', [
            'id' => $this->primaryKey(),
            'idempotency_key' => $this->string(128)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_async_idempotency_log_key', 'async_idempotency_log', 'idempotency_key', true);
        $this->createIndex('idx_async_idempotency_log_created_at', 'async_idempotency_log', 'created_at');
    }
}
