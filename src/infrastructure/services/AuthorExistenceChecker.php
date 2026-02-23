<?php

declare(strict_types=1);

namespace app\infrastructure\services;

use app\application\ports\AuthorExistenceCheckerInterface;
use app\infrastructure\persistence\Author;
use yii\db\Connection;

final readonly class AuthorExistenceChecker implements AuthorExistenceCheckerInterface
{
    public function __construct(
        private Connection $db,
    ) {
    }

    public function existsByFio(string $fio, ?int $excludeId = null): bool
    {
        $query = Author::find()->where(['fio' => $fio]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists($this->db);
    }

    public function existsById(int $id): bool
    {
        return Author::find()->where(['id' => $id])->exists($this->db);
    }

    /**
     * @param array<int> $ids
     */
    public function existsAllByIds(array $ids): bool
    {
        if ($ids === []) {
            return true;
        }

        $uniqueIds = array_values(array_unique($ids));
        $existingCount = (int) Author::find()
            ->where(['id' => $uniqueIds])
            ->count('DISTINCT id', $this->db);

        return $existingCount === count($uniqueIds);
    }
}
