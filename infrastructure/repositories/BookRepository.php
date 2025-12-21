<?php

declare(strict_types=1);

namespace app\infrastructure\repositories;

use app\application\books\queries\BookReadDto;
use app\application\common\adapters\QueryResult;
use app\application\ports\BookRepositoryInterface;
use app\application\ports\PagedResultInterface;
use app\models\Author;
use app\models\Book;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;

final class BookRepository implements BookRepositoryInterface
{
    public function create(
        string $title,
        int $year,
        string $isbn,
        ?string $description,
        ?string $coverUrl
    ): int {
        $book = Book::create(
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverUrl: $coverUrl
        );

        if (!$book->save()) {
            $errors = $book->getFirstErrors();
            $message = $errors ? array_shift($errors) : 'Failed to save book';
            throw new \RuntimeException($message);
        }

        return $book->id;
    }

    public function update(
        int $id,
        string $title,
        int $year,
        string $isbn,
        ?string $description,
        ?string $coverUrl
    ): void {
        $book = Book::findOne($id);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        $book->edit(
            title: $title,
            year: $year,
            isbn: $isbn,
            description: $description,
            coverUrl: $coverUrl
        );

        if (!$book->save()) {
            $errors = $book->getFirstErrors();
            $message = $errors ? array_shift($errors) : 'Failed to update book';
            throw new \RuntimeException($message);
        }
    }

    public function findById(int $id): ?BookReadDto
    {
        $book = Book::findOne($id);
        if (!$book) {
            return null;
        }

        return $this->mapToDto($book);
    }

    public function delete(int $id): void
    {
        $book = Book::findOne($id);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        if (!$book->delete()) {
            throw new \RuntimeException('Failed to delete book');
        }
    }

    public function syncAuthors(int $bookId, array $newAuthorIds): void
    {
        $book = Book::findOne($bookId);
        if (!$book) {
            return;
        }

        $existingAuthorIds = $book->getAuthors()->select('id')->column();
        $existingAuthorIds = array_map('intval', $existingAuthorIds);
        $newAuthorIds = array_map('intval', $newAuthorIds);

        $toDelete = array_diff($existingAuthorIds, $newAuthorIds);
        $toAdd = array_diff($newAuthorIds, $existingAuthorIds);

        if ($toDelete) {
            \Yii::$app->db->createCommand()->delete('book_authors', [
                'and',
                ['book_id' => $bookId],
                ['in', 'author_id', $toDelete],
            ])->execute();
        }

        if (!$toAdd) {
            return;
        }

        $rows = array_map(
            fn($authorId) => [$bookId, $authorId],
            $toAdd
        );
        \Yii::$app->db->createCommand()->batchInsert(
            'book_authors',
            ['book_id', 'author_id'],
            $rows
        )->execute();
    }

    public function findByIdWithAuthors(int $id): ?BookReadDto
    {
        $book = Book::find()->byId($id)->withAuthors()->one();
        if (!$book) {
            return null;
        }

        return $this->mapToDto($book);
    }

    public function search(string $term, int $pageSize): PagedResultInterface
    {
        $query = Book::find()->withAuthors()->orderedByCreatedAt();

        if ($term !== '') {
            $this->applySearchConditions($query, $term);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
        ]);

        $models = array_map(
            fn(Book $model) => $this->mapToDto($model),
            $dataProvider->getModels()
        );

        return new QueryResult(
            models: $models,
            totalCount: $dataProvider->getTotalCount(),
            pagination: $dataProvider->getPagination()
        );
    }

    public function existsByIsbn(string $isbn, ?int $excludeId = null): bool
    {
        $query = Book::find()->andWhere(['isbn' => $isbn]);

        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        return $query->exists();
    }

    private function mapToDto(Book $book): BookReadDto
    {
        $authorNames = [];
        foreach ($book->authors as $author) {
            $authorNames[$author->id] = $author->fio;
        }

        return new BookReadDto(
            id: $book->id,
            title: $book->title,
            year: $book->year,
            description: $book->description,
            isbn: $book->isbn,
            authorIds: array_keys($authorNames),
            authorNames: $authorNames,
            coverUrl: $book->cover_url
        );
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
