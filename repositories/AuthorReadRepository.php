<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\Author;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

final class AuthorReadRepository
{
    public function getIndexDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $this->findAllOrderedByFio(),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }

    public function findAllOrderedByFio(): ActiveQuery
    {
        return Author::find()->orderBy(['fio' => SORT_ASC]);
    }

    public function findById(int $id): Author|null
    {
        return Author::findOne($id);
    }

    public function search(string $search, int $page, int $pageSize): array
    {
        $query = Author::find()
            ->orderBy(['fio' => SORT_ASC]);

        if ($search !== '') {
            $query->andWhere(['like', 'fio', $search]);
        }

        $total = $query->count();
        $authors = $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all();

        return [
            'items' => $authors,
            'total' => $total,
        ];
    }
}
