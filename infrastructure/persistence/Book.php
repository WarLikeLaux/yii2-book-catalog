<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use app\application\books\queries\BookReadDto;
use AutoMapper\Attribute\MapTo;
use yii\behaviors\OptimisticLockBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int|null $id
 * @property string $title
 * @property int $year
 * @property string $isbn
 * @property string|null $description
 * @property string|null $cover_url
 * @property int $is_published
 * @property int $version
 * @property int $created_at
 * @property int $updated_at
 * @property Author[] $authors
 */
final class Book extends ActiveRecord
{
    public static function find(): BookQuery
    {
        return new BookQuery(static::class);
    }

    public static function tableName(): string
    {
        return 'books';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            [
                'class' => OptimisticLockBehavior::class,
                'value' => fn(): int => $this->version ?? 1,
            ],
        ];
    }

    public function optimisticLock(): string
    {
        return 'version';
    }

    public function rules(): array
    {
        return [
            [['title', 'year', 'isbn'], 'required'],
            [['year'], 'integer', 'min' => 1000, 'max' => (int)date('Y') + 1],
            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['isbn'], 'string', 'max' => 20],
            [['cover_url'], 'string', 'max' => 500],
            [['is_published'], 'boolean'],
        ];
    }

    /** @return int[] */
    #[MapTo(target: BookReadDto::class, property: 'authorIds')]
    public function getAuthorIds(): array
    {
        $ids = [];

        foreach ($this->authors as $author) {
            $ids[] = (int)$author->id;
        }

        return $ids;
    }

    /** @return array<int, string> */
    #[MapTo(target: BookReadDto::class, property: 'authorNames')]
    public function getAuthorNames(): array
    {
        $names = [];

        foreach ($this->authors as $author) {
            $names[(int)$author->id] = $author->fio;
        }

        return $names;
    }

    public function getAuthors(): ActiveQuery
    {
        return $this->hasMany(Author::class, ['id' => 'author_id'])
            ->viaTable('book_authors', ['book_id' => 'id']);
    }

    #[MapTo(target: BookReadDto::class, property: 'coverUrl')]
    public function getCoverUrl(): ?string
    {
        return $this->cover_url;
    }

    #[MapTo(target: BookReadDto::class, property: 'isPublished')]
    public function getIsPublished(): bool
    {
        return (bool)$this->is_published;
    }
}
