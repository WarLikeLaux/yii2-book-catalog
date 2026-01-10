<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int|null $id
 * @property string $fio
 * @property Book[] $books
 */
final class Author extends ActiveRecord
{
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
