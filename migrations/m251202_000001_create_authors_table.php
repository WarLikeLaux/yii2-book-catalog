<?php

declare(strict_types=1);

use yii\db\Migration;

final class m251202_000001_create_authors_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('authors', [
            'id' => $this->primaryKey(),
            'fio' => $this->string(255)->notNull()->comment('ФИО автора'),
        ]);

        $this->createIndex('idx_authors_fio', 'authors', 'fio');
    }

    public function safeDown(): void
    {
        $this->dropTable('authors');
    }
}
