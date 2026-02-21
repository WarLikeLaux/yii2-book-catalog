<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

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
 * @property string $status
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

    /**
     * @param mixed $_value
     * @param object|array<string, mixed> $_source
     * @param array<string, mixed> $_context
     * @return array<int, string>
     */
    public static function mapAuthorNames(mixed $_value, object|array $_source, array $_context): array
    {
        /** @var array<int, string> */
        return is_array($_value) ? $_value : [];
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
            [['status'], 'string', 'max' => 20],
        ];
    }

    /** @return int[] */
    public function getAuthorIds(): array
    {
        $ids = [];

        foreach ($this->authors as $author) {
            $ids[] = (int)$author->id;
        }

        return $ids;
    }

    /** @return array<int, string> */
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

    public function getCoverUrl(): ?string
    {
        return $this->cover_url;
    }
}
