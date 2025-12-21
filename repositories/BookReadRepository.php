<?php

declare(strict_types=1);

namespace app\repositories;

use app\models\Author;
use app\models\Book;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;

final class BookReadRepository
{
    public function getIndexDataProvider(): ActiveDataProvider
    {
        return new ActiveDataProvider([
            'query' => $this->findAllWithAuthors(),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);
    }

    public function findAllWithAuthors(): ActiveQuery
    {
        return Book::find()->withAuthors()->orderedByCreatedAt();
    }

    public function findById(int $id): Book|null
    {
        return Book::findOne($id);
    }

    public function findByIdWithAuthors(int $id): Book|null
    {
        return Book::find()->byId($id)->withAuthors()->one();
    }

    public function search(string $term, int $pageSize = 9): ActiveDataProvider
    {
        $query = $this->findAllWithAuthors();

        if ($term !== '') {
            $this->applySearchConditions($query, $term);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
        ]);
    }

    private function applySearchConditions(ActiveQuery $query, string $term): void
    {
        $conditions = ['or'];

        if (preg_match('/^\d{4}$/', $term)) {
            $conditions[] = ['year' => (int)$term];
        }

        $conditions[] = ['like', 'isbn', $term . '%', false];
        $conditions[] = $this->buildAuthorCondition($term);

        $fulltextQuery = $this->prepareFulltextQuery($term);
        if ($fulltextQuery) {
            $conditions[] = new Expression(
                'MATCH(title, description) AGAINST(:query IN BOOLEAN MODE)',
                [':query' => $fulltextQuery]
            );
        }

        $query->andWhere($conditions);
    }

    private function prepareFulltextQuery(string $term): string
    {
        $term = preg_replace('/[+\-><()~*\"@]+/', ' ', $term);
        $words = array_filter(explode(' ', trim($term)));

        return empty($words) ? '' : '+' . implode('* +', $words) . '*';
    }

    private function buildAuthorCondition(string $term): array
    {
        $subQuery = Author::find()
            ->select(new Expression('1'))
            ->innerJoin('book_authors ba', 'authors.id = ba.author_id')
            ->where('ba.book_id = books.id')
            ->andWhere(['like', 'authors.fio', $term]);

        return ['exists', $subQuery];
    }
}
