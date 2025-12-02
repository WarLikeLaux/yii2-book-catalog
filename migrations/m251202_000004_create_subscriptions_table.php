<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251202_000004_create_subscriptions_table extends Migration
{
    public function safeUp(): void
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
            'CASCADE'
        );

        $this->createIndex('idx_subscriptions_phone_author', 'subscriptions', ['phone', 'author_id'], true);
    }

    public function safeDown(): void
    {
        $this->dropTable('subscriptions');
    }
}

