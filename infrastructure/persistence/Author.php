<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class Author extends ActiveRecord
{
    public static function create(string $fio): self
    {
        $author = new self();
        $author->fio = $fio;
        return $author;
    }

    public function edit(string $fio): void
    {
        $this->fio = $fio;
    }

    public static function tableName(): string
    {
        return 'authors';
    }

    public function rules(): array
    {
        return [
            [['fio'], 'required'],
            [['fio'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'fio' => 'ФИО',
        ];
    }

    public function getBooks(): ActiveQuery
    {
        return $this->hasMany(Book::class, ['id' => 'book_id'])
            ->viaTable('book_authors', ['author_id' => 'id']);
    }
}
