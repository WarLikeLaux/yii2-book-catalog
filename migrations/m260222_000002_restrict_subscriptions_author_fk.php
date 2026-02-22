<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260222_000002_restrict_subscriptions_author_fk extends Migration
{
    public function safeUp(): void
    {
        $this->dropForeignKey('fk_subscriptions_author', 'subscriptions');

        $this->addForeignKey(
            'fk_subscriptions_author',
            'subscriptions',
            'author_id',
            'authors',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_subscriptions_author', 'subscriptions');

        $this->addForeignKey(
            'fk_subscriptions_author',
            'subscriptions',
            'author_id',
            'authors',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }
}
