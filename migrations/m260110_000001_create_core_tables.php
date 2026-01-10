<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260110_000001_create_core_tables extends Migration
{
    public function up(): void
    {
        $this->createAuthorsTable();
        $this->createBooksTable();
    }

    public function down(): void
    {
        $this->dropTable('books');
        $this->dropTable('authors');
    }

    private function createAuthorsTable(): void
    {
        $this->createTable('authors', [
            'id' => $this->primaryKey(),
            'fio' => $this->string(255)->notNull()->comment('ФИО автора'),
        ]);

        $this->createIndex('idx_authors_fio_unique', 'authors', 'fio', true);

        if ($this->db->driverName !== 'mysql') {
            return;
        }

        $this->execute('ALTER TABLE ' . $this->db->quoteTableName('authors') . ' ADD FULLTEXT INDEX idx_authors_fio_fulltext (fio)');
    }

    private function createBooksTable(): void
    {
        $this->createTable('books', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull()->comment('Название книги'),
            'year' => $this->integer()->notNull()->comment('Год выпуска'),
            'description' => $this->text()->comment('Описание'),
            'isbn' => $this->string(20)->notNull()->comment('ISBN'),
            'cover_url' => $this->string(500)->comment('URL обложки'),
            'is_published' => $this->boolean()->notNull()->defaultValue(false)->comment('Опубликована'),
            'version' => $this->integer()->notNull()->defaultValue(0)->comment('Версия для оптимистичной блокировки'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_books_year', 'books', 'year');
        $this->createIndex('idx_books_title', 'books', 'title');
        $this->createIndex('idx_books_isbn', 'books', 'isbn', true);

        if ($this->db->driverName === 'mysql') {
            $tableName = $this->db->quoteTableName('books');

            $this->execute("ALTER TABLE {$tableName} ADD FULLTEXT INDEX idx_books_title_desc_fulltext (title, description)");
            $this->execute("CREATE INDEX idx_books_cover_url_partial ON {$tableName} (cover_url(50))");
        } elseif ($this->db->driverName === 'pgsql') {
            $this->execute('CREATE INDEX idx_books_cover_url_partial ON ' . $this->db->quoteTableName('books') . ' (cover_url) WHERE cover_url IS NOT NULL');
        }
    }
}
