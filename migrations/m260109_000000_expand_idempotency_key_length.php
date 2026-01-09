<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260109_000000_expand_idempotency_key_length extends Migration
{
    public function safeUp(): void
    {
        $this->alterColumn('idempotency_keys', 'idempotency_key', $this->string(128)->notNull());
    }

    public function safeDown(): void
    {
        $this->alterColumn('idempotency_keys', 'idempotency_key', $this->string(36)->notNull());
    }
}
