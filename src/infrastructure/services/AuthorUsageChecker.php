<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use app\application\ports\AuthorUsageCheckerInterface;
use app\domain\values\BookStatus;
use yii\db\Connection;
use yii\db\Query;

final readonly class AuthorUsageChecker implements AuthorUsageCheckerInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function isLinkedToPublishedBooks(int $authorId): bool
    {
        return (new Query())
            ->from('book_authors')
            ->innerJoin('books', 'books.id = book_authors.book_id')
            ->where(['book_authors.author_id' => $authorId])
            ->andWhere(['books.status' => [BookStatus::Published->value, BookStatus::Archived->value]])
            ->exists($this->db);
    }

    public function hasSubscriptions(int $authorId): bool
    {
        return (new Query())
            ->from('subscriptions')
            ->where(['author_id' => $authorId])
            ->exists($this->db);
    }
}
