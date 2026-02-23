<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use app\application\ports\BookIsbnCheckerInterface;
use app\infrastructure\persistence\Book;
use yii\db\Connection;

final readonly class BookIsbnChecker implements BookIsbnCheckerInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        $query = Book::find()->where(['isbn' => $isbn]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists($this->db);
    }
}
