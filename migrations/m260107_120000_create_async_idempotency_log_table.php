<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260107_120000_create_async_idempotency_log_table extends Migration
{
    /**
     * Create the `async_idempotency_log` table and add an index on `created_at`.
     *
     * The table contains:
     * - `id`: primary key
     * - `idempotency_key`: string(128), not null, unique
     * - `created_at`: integer, not null
     */
    public function safeUp(): void
    {
        $this->createTable('async_idempotency_log', [
            'id' => $this->primaryKey(),
            'idempotency_key' => $this->string(128)->notNull()->unique(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_async_idempotency_log_created_at', 'async_idempotency_log', 'created_at');
    }

    /**
     * Reverts the migration by dropping the `async_idempotency_log` table.
     *
     * This removes the table and any associated indexes.
     */
    public function safeDown(): void
    {
        $this->dropTable('async_idempotency_log');
    }
}