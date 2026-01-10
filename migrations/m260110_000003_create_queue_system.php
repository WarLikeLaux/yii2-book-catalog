<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260110_000003_create_queue_system extends Migration
{
    public function up(): void
    {
        $this->createQueueTable();
        $this->createSubscriptionsTable();
    }

    public function down(): void
    {
        $this->dropTable('subscriptions');
        $this->dropTable('{{%queue}}');
    }

    private function createQueueTable(): void
    {
        $this->createTable('{{%queue}}', [
            'id' => $this->primaryKey(),
            'channel' => $this->string()->notNull(),
            'job' => $this->binary()->notNull(),
            'pushed_at' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->notNull()->defaultValue(0),
            'priority' => $this->integer()->unsigned()->notNull()->defaultValue(1024),
            'reserved_at' => $this->integer(),
            'attempt' => $this->integer(),
            'done_at' => $this->integer(),
        ]);

        $this->createIndex('idx_queue_reserved_at', '{{%queue}}', 'reserved_at');
        $this->createIndex(
            'idx_queue_channel_reserved_at_priority',
            '{{%queue}}',
            ['channel', 'reserved_at', 'priority'],
        );
    }

    private function createSubscriptionsTable(): void
    {
        $this->createTable('subscriptions', [
            'id' => $this->primaryKey(),
            'phone' => $this->string(20)->notNull()->comment('Номер телефона'),
            'author_id' => $this->integer()->notNull()->comment('ID автора'),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_subscriptions_author',
            'subscriptions',
            'author_id',
            'authors',
            'id',
            'CASCADE',
            'CASCADE',
        );

        $this->createIndex('idx_subscriptions_phone_author', 'subscriptions', ['phone', 'author_id'], true);
    }
}
