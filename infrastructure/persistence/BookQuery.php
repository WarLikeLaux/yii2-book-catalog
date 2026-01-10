<?php

declare(strict_types=1);

namespace app\infrastructure\persistence;

use yii\db\ActiveQuery;

/**
 * @extends ActiveQuery<Book>
 * @method Book[] all($db = null)
 * @method Book|null one($db = null)
 */
final class BookQuery extends ActiveQuery
{
    public function withAuthors(): self
    {
        return $this->with('authors');
    }

    public function orderedByCreatedAt(): self
    {
        return $this->orderBy(['created_at' => SORT_DESC]);
    }

    public function byId(int $id): self
    {
        return $this->where(['id' => $id]);
    }
}
