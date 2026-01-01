<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260101_193500_update_idempotency_keys_table extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('idempotency_keys', 'status', $this->string(20)->notNull()->defaultValue('finished'));
        $this->alterColumn('idempotency_keys', 'status_code', $this->integer()->null());
        $this->alterColumn('idempotency_keys', 'response_body', $this->binary()->null());
    }

    public function safeDown(): void
    {
        $this->alterColumn('idempotency_keys', 'response_body', $this->binary()->notNull());
        $this->alterColumn('idempotency_keys', 'status_code', $this->integer()->notNull());
        $this->dropColumn('idempotency_keys', 'status');
    }
}
