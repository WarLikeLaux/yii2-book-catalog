<?php

declare(strict_types=1);

namespace app\models;

use app\validators\IsbnValidator;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class Book extends ActiveRecord
{
    public static function find(): BookQuery
    {
        return new BookQuery(static::class);
    }

    public static function create(
        string $title,
        int $year,
        string $isbn,
        ?string $description,
        ?string $coverUrl
    ): self {
        $book = new self();
        $book->title = $title;
        $book->year = $year;
        $book->isbn = $isbn;
        $book->description = $description;
        $book->cover_url = $coverUrl;

        return $book;
    }

    public function edit(
        string $title,
        int $year,
        string $isbn,
        ?string $description,
        ?string $coverUrl
    ): void {
        $this->title = $title;
        $this->year = $year;
        $this->isbn = $isbn;
        $this->description = $description;
        if ($coverUrl === null) {
            return;
        }

        $this->cover_url = $coverUrl;
    }

    public static function tableName(): string
    {
        return 'books';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => (int)date('Y') + 1],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['isbn'], 'string', 'max' => 20],
            [['isbn'], IsbnValidator::class],
            [['isbn'], 'unique'],
            [['cover_url'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'year' => 'Год выпуска',
            'description' => 'Описание',
            'isbn' => 'ISBN',
            'cover_url' => 'Обложка',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    public function getAuthors(): ActiveQuery
    {
        return $this->hasMany(Author::class, ['id' => 'author_id'])
            ->viaTable('book_authors', ['book_id' => 'id']);
    }
}
