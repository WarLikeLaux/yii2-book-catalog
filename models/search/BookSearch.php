<?php

declare(strict_types=1);

namespace app\models\search;

use app\models\Author;
use app\models\Book;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;

final class BookSearch extends Model
{
    public string $globalSearch = '';

    public function rules(): array
    {
        return [
            [['globalSearch'], 'string', 'min' => 2],
            [['globalSearch'], 'trim'],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Book::find()->with('authors');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 9],
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        if (!$this->load($params) || !$this->validate() || empty($this->globalSearch)) {
            return $dataProvider;
        }

        $this->applySearchConditions($query);

        return $dataProvider;
    }

    private function applySearchConditions(ActiveQuery $query): void
    {
        $term = $this->globalSearch;
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

        if (empty($words)) {
            return '';
        }

        return '+' . implode('* +', $words) . '*';
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
